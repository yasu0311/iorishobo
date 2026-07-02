<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FileTypeSeeder::class,
            GradeSeeder::class,
            SubjectSeeder::class,
            ConsumptionTaxSeeder::class,
            SettingSeeder::class,
            InformationSeeder::class,
            DepositSeeder::class,
            ProductSeeder::class,
            ProductFileSeeder::class,
            OrderSeeder::class,
            MessageSeeder::class,
            MessageReplySeeder::class,
            ReviewSeeder::class,
            ReviewReplySeeder::class,
            WithdrawalSeeder::class,
            FavoriteSeeder::class,
            ExpiredBalanceSeeder::class,
        ]);
    }
}
