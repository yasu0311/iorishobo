<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\ExpiredBalance;

class WalletExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ウォレット残高の有効期限（6か月）に基づき失効処理を行う';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('ウォレット残高の失効処理を開始します...');

        $now = now();
        $expiryMonths = Setting::getValue('BALANCE_EXPIRY_MONTHS') ?? 6;
        $expiryThreshold = $now->copy()->subMonthsNoOverflow($expiryMonths);

        $this->info("基準日時: {$now->toDateTimeString()} / 失効対象の起点: {$expiryThreshold->toDateTimeString()}");

        // 失効対象となる入金・売上を持つメンバーIDを集約
        $targetMemberIds = collect();

        // deposits 起点
        $depositMemberIds = DB::table('deposits')
            ->select('member_id')
            ->where('status', 2)
            ->where('deposited_at', '<=', $expiryThreshold)
            ->groupBy('member_id')
            ->pluck('member_id');

        $targetMemberIds = $targetMemberIds->merge($depositMemberIds);

        // 売上起点（自分のショップの売上）
        $orderMemberIds = DB::table('orders')
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            ->join('members', 'shops.member_id', '=', 'members.id')
            ->select('members.id as member_id')
            ->whereNull('orders.canceled_at')
            ->where('orders.status', 'completed')
            ->where('orders.total_amount', '>', 0)
            ->where('orders.ordered_at', '<=', $expiryThreshold)
            ->groupBy('members.id')
            ->pluck('member_id');

        $targetMemberIds = $targetMemberIds->merge($orderMemberIds)->unique()->values();

        if ($targetMemberIds->isEmpty()) {
            $this->info('失効対象となるメンバーはありませんでした。');
            return Command::SUCCESS;
        }

        $this->info('対象メンバー数: ' . $targetMemberIds->count());

        foreach ($targetMemberIds as $memberId) {
            DB::transaction(function () use ($memberId, $expiryThreshold) {
                // target: 失効対象となる残高（入金＋売上）
                $depositTarget = DB::table('deposits')
                    ->where('member_id', $memberId)
                    ->where('status', 2)
                    ->where('deposited_at', '<=', $expiryThreshold)
                    ->sum('amount');

                $orderTarget = DB::table('orders')
                    ->join('products', 'orders.product_id', '=', 'products.id')
                    ->join('shops', 'products.shop_id', '=', 'shops.id')
                    ->where('shops.member_id', $memberId)
                    ->whereNull('orders.canceled_at')
                    ->where('orders.status', 'completed')
                    ->where('orders.total_amount', '>', 0)
                    ->where('orders.ordered_at', '<=', $expiryThreshold)
                    ->sum('orders.total_amount');

                $target = $depositTarget + $orderTarget;

                if ($target <= 0) {
                    return;
                }

                // consumed: これまでに使われた残高（購入 + 出金）
                $consumedOrders = DB::table('orders')
                    ->where('member_id', $memberId)
                    ->whereNull('canceled_at')
                    ->where('status', 'completed')
                    ->sum('points_paid');

                $consumedWithdrawals = DB::table('withdrawals')
                    ->where('member_id', $memberId)
                    ->whereIn('status', [1, 2])
                    ->sum('amount');

                $consumed = $consumedOrders + $consumedWithdrawals;

                // already_expired: すでに失効記録済みの金額
                $alreadyExpired = DB::table('expired_balances')
                    ->where('member_id', $memberId)
                    ->sum('amount');

                // expire_amount = max(target - least(consumed, target) - already_expired, 0)
                $effectiveConsumed = min($consumed, $target);
                $expireAmount = max($target - $effectiveConsumed - $alreadyExpired, 0);

                if ($expireAmount <= 0) {
                    return;
                }

                ExpiredBalance::create([
                    'member_id' => $memberId,
                    'amount' => $expireAmount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }

        $this->info('ウォレット残高の失効処理が完了しました。');

        return Command::SUCCESS;
    }
}

