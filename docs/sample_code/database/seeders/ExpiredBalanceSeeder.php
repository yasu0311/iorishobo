<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\ExpiredBalance;

class ExpiredBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $member = Member::first();

        if (! $member) {
            $this->command?->warn('Memberレコードがないため、ExpiredBalanceSeederはスキップされました。');
            return;
        }

        // 表示確認用に、1件だけシンプルな失効レコードを作成
        ExpiredBalance::create([
            'member_id' => $member->id,
            'amount' => 1000,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->command?->info("ExpiredBalanceSeeder: member_id={$member->id} に 1000 円の失効レコードを1件作成しました。");
    }
}

