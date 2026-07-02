<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class ProductionSettingSeeder extends Seeder
{
    public function run(): void
    {
        $globalSettings = [
            ['can_override' => true, 'setting_key' => 'total_upload_limit', 'value_type' => 5, 'value_bigint' => 500000000, 'description' => '全体のアップロード容量上限(Byte)'],
            ['can_override' => false, 'setting_key' => 'withdrawal_fee', 'value_type' => 1, 'value_int' => 200, 'description' => '出金手数料'],
            ['can_override' => false, 'setting_key' => 'withdrawal_fee_free_threshold', 'value_type' => 1, 'value_int' => 30000, 'description' => '出金手数料無料となる金額'],
            ['can_override' => false, 'setting_key' => 'minimum_withdrawal_amount', 'value_type' => 1, 'value_int' => 1000, 'description' => '最低出金金額'],
            ['can_override' => false, 'setting_key' => 'minimum_listing_price', 'value_type' => 1, 'value_int' => 50, 'description' => '最低出品価格(税抜)'],
            ['can_override' => false, 'setting_key' => 'maximum_listing_price', 'value_type' => 1, 'value_int' => 2147483647, 'description' => '最大出品価格(税抜)'],
            ['can_override' => true, 'setting_key' => 'transaction_fee_rate', 'value_type' => 2, 'value_decimal' => 0.15, 'description' => '販売手数料率'],
            ['can_override' => true, 'setting_key' => 'listing_limit', 'value_type' => 1, 'value_int' => 30, 'description' => '出品数の上限'],
            ['can_override' => true, 'setting_key' => 'single_file_upload_limit', 'value_type' => 5, 'value_bigint' => 104857600, 'description' => '1ファイルあたりのアップロード容量上限(Byte)'],
            ['can_override' => true, 'setting_key' => 'product_files_limit', 'value_type' => 1, 'value_int' => 50, 'description' => '1商品あたりの商品ファイル登録上限(件)'],
            ['can_override' => false, 'setting_key' => 'BALANCE_EXPIRY_MONTHS', 'value_type' => 1, 'value_int' => 6, 'description' => 'ウォレット残高の有効期限(月数・出金申請期限も同一)'],
            ['can_override' => false, 'setting_key' => 'BALANCE_REMINDER_FIRST_BEFORE_DAYS', 'value_type' => 1, 'value_int' => 30, 'description' => '残高有効期限のリマインド1回目(日数)'],
            ['can_override' => false, 'setting_key' => 'BALANCE_REMINDER_SECOND_BEFORE_DAYS', 'value_type' => 1, 'value_int' => 7, 'description' => '残高有効期限のリマインド2回目(日数)'],
            ['can_override' => false, 'setting_key' => 'BALANCE_DATA_RETENTION_YEARS', 'value_type' => 1, 'value_int' => 7, 'description' => '取引情報・残高情報の保存年数'],
        ];

        foreach ($globalSettings as $row) {
            Setting::updateOrCreate(
                [
                    'type' => 1,
                    'shop_id' => null,
                    'setting_key' => $row['setting_key'],
                ],
                array_merge(
                    [
                        'can_override' => $row['can_override'],
                        'value_type' => $row['value_type'],
                        'description' => $row['description'],
                        'value_int' => null,
                        'value_decimal' => null,
                        'value_string' => null,
                        'value_tinyint' => null,
                        'value_boolean' => null,
                        'value_bigint' => null,
                    ],
                    $row
                )
            );
        }
    }
}
