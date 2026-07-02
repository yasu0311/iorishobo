<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reasons = [
            'チャージ',
            'キャンペーン付与',
            '返金',
            'ポイント交換',
        ];

        $remarks = [
            'テストデータ',
            '初期残高調整',
            'システム登録',
            '自動生成',
        ];

        return [
            'status' => 2,
            'amount' => random_int(1000, 100000),
            'deposited_at' => now()->subDays(random_int(0, 100))->toDateTimeString(),
            'deposit_reason' => rand(1, 10) <= 8 ? $reasons[array_rand($reasons)] : null,
            'remark' => rand(1, 10) <= 8 ? $remarks[array_rand($remarks)] : null,
        ];
    }
}
