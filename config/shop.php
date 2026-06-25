<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 店舗基本情報
    |--------------------------------------------------------------------------
    |
    | フッター・特定商取引法・お問い合わせ等で使用する。
    | 値は .env の SHOP_* キーから読み込む（§3.18）。
    |
    */

    'name' => env('SHOP_NAME', env('APP_NAME', 'いおり書房')),

    'phone' => env('SHOP_PHONE', ''),

    'email' => env('SHOP_EMAIL', ''),

    'address' => [
        'postal_code' => env('SHOP_POSTAL_CODE', ''),
        'prefecture' => env('SHOP_PREFECTURE', ''),
        'address_line1' => env('SHOP_ADDRESS_LINE1', ''),
        'address_line2' => env('SHOP_ADDRESS_LINE2', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | インボイス（適格請求書）
    |--------------------------------------------------------------------------
    */

    'invoice_registration_number' => env('SHOP_INVOICE_REGISTRATION_NUMBER', ''),

    /*
    |--------------------------------------------------------------------------
    | 振込先口座（銀行振込案内）
    |--------------------------------------------------------------------------
    */

    'bank_account' => [
        'bank_name' => env('SHOP_BANK_NAME', ''),
        'branch_name' => env('SHOP_BANK_BRANCH_NAME', ''),
        'account_type' => env('SHOP_BANK_ACCOUNT_TYPE', '普通'),
        'account_number' => env('SHOP_BANK_ACCOUNT_NUMBER', ''),
        'account_holder' => env('SHOP_BANK_ACCOUNT_HOLDER', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | 代金引換手数料
    |--------------------------------------------------------------------------
    |
    | cod_free_threshold: クーポン適用後の商品合計がこの金額以上なら手数料 0 円。
    | 空欄 = 無料ラインなし（常に cod_fee を課金）。
    |
    */

    'cod_fee' => (int) env('SHOP_COD_FEE', 330),

    'cod_free_threshold' => filled(env('SHOP_COD_FREE_THRESHOLD'))
        ? (int) env('SHOP_COD_FREE_THRESHOLD')
        : null,

];
