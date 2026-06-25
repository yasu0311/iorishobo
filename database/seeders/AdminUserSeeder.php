<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * 初回デプロイ用の管理者アカウント（users.is_admin = true）。
     *
     * @see docs/specification.md §3.15
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');

        if (! filled($email)) {
            $this->command?->warn('ADMIN_EMAIL が未設定のため AdminUserSeeder をスキップしました。');

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', '管理者'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
