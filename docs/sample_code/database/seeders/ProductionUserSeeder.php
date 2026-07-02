<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// envファイルに書く
// PROD_ADMIN_EMAIL=あなたの管理者メール@example.com
// PROD_ADMIN_NAME=本番管理者
// PROD_ADMIN_DISPLAY_NAME=管理人
// PROD_ADMIN_PASSWORD=強いランダムなパスワード

class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PROD_ADMIN_EMAIL', 'admin@example.com');
        $name = env('PROD_ADMIN_NAME', '本番管理者');
        $adminDisplayName = env('PROD_ADMIN_DISPLAY_NAME', '管理人');
        $password = env('PROD_ADMIN_PASSWORD');

        if (empty($password)) {
            $this->command?->warn('PROD_ADMIN_PASSWORD が未設定です。仮パスワードを使用します。実行後に必ず変更してください。');
            $password = 'ChangeMeNow!123';
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 1,
                'status' => 1,
                'email_verified_at' => now(),
            ]
        );

        Admin::updateOrCreate(
            ['user_id' => $user->id],
            ['name' => $adminDisplayName]
        );
    }
}
