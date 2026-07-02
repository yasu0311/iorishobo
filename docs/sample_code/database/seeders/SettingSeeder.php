<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['type' => 1, 'shop_id' => null, 'can_override' => true, 'setting_key' => 'total_upload_limit', 'value_type' => 5, 'value_bigint' => 500000000, 'description' => '全体のアップロード容量上限(Byte)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'withdrawal_fee', 'value_type' => 1, 'value_int' => 200, 'description' => '出金手数料'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'withdrawal_fee_free_threshold', 'value_type' => 1, 'value_int' => 30000, 'description' => '出金手数料無料となる金額'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'minimum_withdrawal_amount', 'value_type' => 1, 'value_int' => 1000, 'description' => '最低出金金額'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'minimum_listing_price', 'value_type' => 1, 'value_int' => 50, 'description' => '最低出品価格(税抜)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'maximum_listing_price', 'value_type' => 1, 'value_int' => 300000, 'description' => '最大出品価格(税抜)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => true, 'setting_key' => 'transaction_fee_rate', 'value_type' => 2, 'value_decimal' => 0.15, 'description' => '販売手数料率'],
            ['type' => 1, 'shop_id' => null, 'can_override' => true, 'setting_key' => 'listing_limit', 'value_type' => 1, 'value_int' => 30, 'description' => '出品数の上限'],
            ['type' => 1, 'shop_id' => null, 'can_override' => true, 'setting_key' => 'single_file_upload_limit', 'value_type' => 5, 'value_bigint' => 104857600, 'description' => '1ファイルあたりのアップロード容量上限(Byte)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => true, 'setting_key' => 'product_files_limit', 'value_type' => 1, 'value_int' => 50, 'description' => '1商品あたりの商品ファイル登録上限(件)'],
            // 残高有効期限・リマインド・データ保存期間
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'BALANCE_EXPIRY_MONTHS', 'value_type' => 1, 'value_int' => 6, 'description' => 'ウォレット残高の有効期限(月数・出金申請期限も同一)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'BALANCE_REMINDER_FIRST_BEFORE_DAYS', 'value_type' => 1, 'value_int' => 30, 'description' => '残高有効期限のリマインド1回目(日数)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'BALANCE_REMINDER_SECOND_BEFORE_DAYS', 'value_type' => 1, 'value_int' => 7, 'description' => '残高有効期限のリマインド2回目(日数)'],
            ['type' => 1, 'shop_id' => null, 'can_override' => false, 'setting_key' => 'BALANCE_DATA_RETENTION_YEARS', 'value_type' => 1, 'value_int' => 7, 'description' => '取引情報・残高情報の保存年数'],

            ['type' => 2, 'shop_id' => 2, 'can_override' => true, 'setting_key' => 'total_upload_limit', 'value_type' => 5, 'value_bigint' => 800000000, 'description' => 'ショップ別アップロード容量上限(Byte)'],
            ['type' => 2, 'shop_id' => 2, 'can_override' => true, 'setting_key' => 'transaction_fee_rate', 'value_type' => 2, 'value_decimal' => 0.07, 'description' => 'ショップ別販売手数料率'],
            ['type' => 2, 'shop_id' => 2, 'can_override' => true, 'setting_key' => 'listing_limit', 'value_type' => 1, 'value_int' => 30, 'description' => 'ショップ別出品数の上限'],
        ];

        foreach ($data as $row) {
            Setting::create($row);
        }
    }
}