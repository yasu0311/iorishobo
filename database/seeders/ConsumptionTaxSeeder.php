<?php

namespace Database\Seeders;

use App\Models\ConsumptionTax;
use Illuminate\Database\Seeder;

class ConsumptionTaxSeeder extends Seeder
{
    /**
     * 消費税率マスタ（期間で切り替える。変更時は DB のみ更新）。
     *
     * @see docs/table-definition.md §1.3
     */
    public function run(): void
    {
        $rows = [
            [
                'start_date' => '2014-04-01',
                'end_date' => '2019-09-30',
                'tax_rate' => 0.08,
            ],
            [
                'start_date' => '2019-10-01',
                'end_date' => '2200-01-01',
                'tax_rate' => 0.10,
            ],
        ];

        foreach ($rows as $row) {
            ConsumptionTax::query()->updateOrCreate(
                [
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                ],
                ['tax_rate' => $row['tax_rate']],
            );
        }
    }
}
