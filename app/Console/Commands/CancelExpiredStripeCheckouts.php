<?php

namespace App\Console\Commands;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Checkout\CheckoutService;
use Illuminate\Console\Command;

class CancelExpiredStripeCheckouts extends Command
{
    protected $signature = 'orders:cancel-expired-stripe-checkouts {--days=7 : 作成から何日経過した未完了 Stripe 注文をキャンセルするか}';

    protected $description = '一定日数経過した stripe + pending の未完了注文を自動キャンセルする';

    public function handle(CheckoutService $checkoutService): int
    {
        $days = (int) $this->option('days');

        $orders = Order::query()
            ->where('payment_method', PaymentMethod::Stripe)
            ->where('payment_status', PaymentStatus::Pending)
            ->where('ordered_at', '<', now()->subDays($days))
            ->orderBy('id')
            ->get();

        $cancelled = 0;

        foreach ($orders as $order) {
            if ($checkoutService->cancelIncompleteStripeCheckout(
                $order,
                CheckoutService::CANCEL_REASON_EXPIRED,
            )) {
                $cancelled++;
            }
        }

        $this->info("キャンセルした未完了 Stripe 注文: {$cancelled} 件");

        return self::SUCCESS;
    }
}
