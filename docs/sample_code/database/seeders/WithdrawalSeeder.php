<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Withdrawal;
use App\Models\Member;

class WithdrawalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のメンバーを取得
        $members = Member::all();
        
        foreach ($members as $member) {
            // 各メンバーに対して2件の出金申請データを作成
            
            // 1件目: 申請中の出金
            Withdrawal::factory()->create([
                'member_id' => $member->id,
                'status' => 1, // 申請中
                'amount' => rand(10000, 100000), // 1万円〜10万円のランダム
                'account_holder' => $member->last_name . ' ' . $member->first_name,
            ]);
            
            // 2件目: 承認済みまたは不許可の出金
            Withdrawal::factory()->create([
                'member_id' => $member->id,
                'status' => rand(2, 3), // 2: 出金済み, 3: 不許可
                'amount' => rand(50000, 200000), // 5万円〜20万円のランダム
                'account_holder' => $member->last_name . ' ' . $member->first_name,
            ]);
        }
    }
}
