<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\Deposit;

class DepositSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::all();

        foreach ($members as $member) {
            // 各メンバーごとに3件作成
            Deposit::factory()
                ->count(3)
                ->create([
                    'member_id' => $member->id,
                ]);
        }
    }
}
