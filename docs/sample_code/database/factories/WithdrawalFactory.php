<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = rand(1, 3); // 1: 申請中, 2: 出金済み, 3: 不許可
        
        return [
            'amount' => rand(1000, 100000),
            'status' => $status,
            'withdrawal_date' => $status === 2 ? now()->subDays(rand(1, 30))->format('Y-m-d') : null,
            'withdrawal_fee' => 550,
            'bank_name' => $this->getRandomBankName(),
            'branch_name' => $this->getRandomBranchName(),
            'account_type' => rand(0, 2), // 0: 普通, 1: 当座, 2: 貯蓄
            'account_number' => $this->generateAccountNumber(),
            'comment' => match($status) {
                1 => 'テストデータ - 出金申請',
                2 => 'テストデータ - 出金完了',
                3 => 'テストデータ - 出金不許可',
            },
            'remark' => $status === 3 ? '口座情報に不備があります' : null,
            'mobile_phone' => match(rand(1, 2)) {
                1 => '090-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                2 => '080-' . rand(1000, 9999) . '-' . rand(1000, 9999),
            },
            'ip_address' => '192.168.1.' . rand(1, 254),
        ];
    }
    
    private function getRandomBankName(): string
    {
        $banks = [
            '三菱UFJ銀行',
            '三井住友銀行',
            'みずほ銀行',
            '楽天銀行',
            'PayPay銀行',
            '住信SBIネット銀行',
            'セブン銀行',
            'ゆうちょ銀行',
        ];
        return $banks[array_rand($banks)];
    }
    
    private function getRandomBranchName(): string
    {
        $branches = [
            '本店',
            '新宿支店',
            '渋谷支店',
            '銀座支店',
            '丸の内支店',
            '品川支店',
            '横浜支店',
            '大阪支店',
        ];
        return $branches[array_rand($branches)];
    }
    
    private function generateAccountNumber(): string
    {
        return (string)rand(1000000, 9999999);
    }
}
