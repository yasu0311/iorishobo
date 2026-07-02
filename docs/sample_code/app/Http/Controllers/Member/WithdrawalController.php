<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\WithdrawalRequest;
use App\Models\Withdrawal;
use App\Models\User;
use App\Models\Setting;
use App\Mail\Withdrawal\WithdrawalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    /**
     * 出金依頼フォームを表示
     */
    public function create()
    {
        $member = Auth::user()->member;
        // 出金可能額を取得（通帳残高）
        $withdrawableAmount = $member->getCurrentBalance();

        // 振込額（出金可能額 − 手数料）が1円以上の場合のみフォーム表示
        $withdrawalFee = Setting::getValue('withdrawal_fee') ?? 0;
        $feeFreeThreshold = Setting::getValue('withdrawal_fee_free_threshold');
        // feeFreeThreshold が null の場合は常に手数料がかかる
        $effectiveFee = ($feeFreeThreshold !== null && $withdrawableAmount >= $feeFreeThreshold) ? 0 : $withdrawalFee;
        $netAmount = $withdrawableAmount - $effectiveFee;
        $canWithdraw = $netAmount >= 1;

        $balanceExpiryMonths = Setting::getValue('BALANCE_EXPIRY_MONTHS') ?? 6;

        return view('member.withdrawals.create', compact('withdrawableAmount', 'canWithdraw', 'balanceExpiryMonths'));
    }

    /**
     * 出金依頼データをバリデーションし、セッションに保存して確認画面へ
     */
    public function store(WithdrawalRequest $request)
    {
        $withdrawal_data = $request->validated();

        $amount = (int) $withdrawal_data['amount'];

        // 現在の残高を再取得（確認画面表示後に変動していないかチェック）
        $member = Auth::user()->member;
        $currentBalance = $member->getCurrentBalance();

        // 手数料（無料閾値を考慮）
        $withdrawal_fee = $this->calculateWithdrawalFee($amount);

        // 出金額が現在の残高を超えていないか念のためチェック
        if ($amount > $currentBalance) {
            return redirect()
                ->route('member.withdrawals.create')
                ->withErrors(['amount' => '出金額が出金可能額を超えています。'])
                ->withInput();
        }

        // 振込額（出金額 − 手数料）が 1 円未満の場合はエラー
        $netAmount = $amount - $withdrawal_fee;
        if ($netAmount < 1) {
            return redirect()
                ->route('member.withdrawals.create')
                ->withErrors(['amount' => '出金額から出金手数料を差し引いた金額が1円未満のため、出金できません。'])
                ->withInput();
        }

        // データベースに保存（トランザクション）
        $withdrawal = DB::transaction(function () use ($member, $amount, $withdrawal_fee, $withdrawal_data, $request) {
            return Withdrawal::create([
                'member_id' => $member->id,
                'amount' => $amount,
                'status' => 1, // 申請中
                'withdrawal_date' => null,
                'withdrawal_fee' => $withdrawal_fee,
                'bank_name' => $withdrawal_data['bank_name'],
                'branch_name' => $withdrawal_data['branch_name'],
                'account_type' => $withdrawal_data['account_type'],
                'account_number' => $withdrawal_data['account_number'],
                'account_holder' =>$withdrawal_data['account_holder'],
                'comment' => isset($withdrawal_data['comment']) ? $withdrawal_data['comment'] : null,
                'mobile_phone' => $withdrawal_data['mobile_phone'],
                'ip_address' => $request->ip(),
            ]);
        });

        // メール送信（DB保存後／キュー経由）
        try {
            $user = Auth::user();
            Mail::to($user->email)->queue(new WithdrawalRequested($withdrawal));
            
            // 管理者にメール送信（role=1の全ユーザー）
            $admins = User::where('role', 1)->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(new WithdrawalRequested($withdrawal));
            }
        } catch (\Throwable $e) {
            Log::error('WithdrawalRequested mail sending failed', [
                'withdrawal_id' => $withdrawal->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
        
        // 完了画面へリダイレクト（再読み込みで二重送信にならないようにする）
        return redirect()->route('member.withdrawals.complete');
    }

    /**
     * 出金依頼確認画面を表示
     */
    public function confirm(WithdrawalRequest $request)
    {
          
        $withdrawal_data = $request->validated();
        // 手数料（無料閾値を考慮）
        $withdrawal_fee = $this->calculateWithdrawalFee((int) $withdrawal_data['amount']);

        return view('member.withdrawals.confirm', compact('withdrawal_data', 'withdrawal_fee'));
    }

    /**
     * 出金依頼完了画面を表示
     */
    public function complete()
    {
        return view('member.withdrawals.complete');
    }

    /**
     * 出金手数料を計算（無料閾値対応）
     */
    private function calculateWithdrawalFee(int $amount): int
    {
        $baseFee = Setting::getValue('withdrawal_fee') ?? 0;
        $feeFreeThreshold = Setting::getValue('withdrawal_fee_free_threshold');

        // 閾値が設定されていて、出金額がそれ以上なら手数料無料
        if ($feeFreeThreshold !== null && $amount >= $feeFreeThreshold) {
            return 0;
        }

        return $baseFee;
    }

}
