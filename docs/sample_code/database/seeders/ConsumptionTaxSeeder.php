<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConsumptionTaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ConsumptionTax::insert([
            [
                'start_date' => '2014-01-01',
                'end_date' => '2019-09-30',
                'tax_rate' => 0,
                'classification_id' => 1,
                'classification' => '非課税・免税',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_date' => '2014-10-01',
                'end_date' => '2019-09-30',
                'tax_rate' => 0.08,
                'classification_id' => 2,
                'classification' => '課税(8%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_date' => '2019-10-01',
                'end_date' => '2200-01-01',
                'tax_rate' => 0,
                'classification_id' => 1,
                'classification' => '非課税・免税',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_date' => '2019-10-01',
                'end_date' => '2200-01-01',
                'tax_rate' => 0.10,
                'classification_id' => 2,
                'classification' => '課税(10%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'start_date' => '2019-10-01',
                'end_date' => '2200-01-01',
                'tax_rate' => 0.08,
                'classification_id' => 3,
                'classification' => '軽減税率(8%)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
