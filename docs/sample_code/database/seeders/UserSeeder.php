<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Shop;


class UserSeeder extends Seeder
{
    private const DEFAULT_MEMBER_ICON_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9YtS2gAAAABJRU5ErkJggg==';
    private const MEMBER_ICON_ASSETS_DIR = 'database/seeders/assets/images';

    public function run(): void
    {
        
        $admin = User::create([
            'id' => 1,
            'name' => '管理者1',
            'email' => 'info@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 1,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Admin::create([
            'user_id' => $admin->id,
            'name' => '管理人１'
        ]);

        $admin = User::create([
            'id' => 2,
            'name' => '管理者2',
            'email' => 'kutar0311@yahoo.co.jp',
            'password' => Hash::make('12345678'),
            'role' => 1,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Admin::create([
            'user_id' => $admin->id,
            'name' => '管理人２'
        ]);

        $member = User::create([
            'id' => 101,
            'name' => '高橋泰宏',
            'email' => 'yasu@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'yasu',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '泰宏',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'ヤスヒロ',
            'postal_code' => '1000001',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '千代田1-1-1',
            'address_building' => rand(1, 10) <= 8 ? '高橋ビル101' : null,
            'phone_number' => '03-1234-5678',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.1' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $member = User::create([
            'id' => 102,
            'name' => '高橋香織',
            'email' => 'kaori@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'kaori',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '香織',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'カオリ',
            'postal_code' => '1000002',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '丸の内1-2-3',
            'address_building' => rand(1, 10) <= 8 ? '香織マンション202' : null,
            'phone_number' => '03-2345-6789',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.2' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $member = User::create([
            'id' => 103,
            'name' => '高橋天馬',
            'email' => 'manmaru@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'manmaru',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '天馬',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'テンマ',
            'postal_code' => '1000003',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '大手町2-3-4',
            'address_building' => rand(1, 10) <= 8 ? '天馬タワー303' : null,
            'phone_number' => '03-3456-7890' ,
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.3' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $tenmaMemberId = Member::where('user_id', $member->id)->value('id');
        if ($tenmaMemberId) {
            $shop = Shop::create([
                'member_id' => $tenmaMemberId,
                'shop_name' => 'tenma',
                'shop_information' => 'tenmaのショップです',
                'shop_introduction' => '天馬さんの教材を販売します',
                'receipt_description' => rand(1, 10) <= 8 ? '領収書の発行が可能です' : null,
                'url' => rand(1, 10) <= 8 ? 'https://tenma-shop.example.com' : null,
                'total_upload_limit' => rand(1, 10) <= 8 ? 1000000000 : null,
                'transaction_fee_rate' => rand(1, 10) <= 8 ? 0.035 : null,
            ]);
            $this->seedShopIcon($shop->id);
        }

        $member = User::create([
            'id' => 104,
            'name' => '高橋古都',
            'email' => 'utagitan@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'utagitan',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '古都',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'コト',
            'postal_code' => '1000004',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '霞が関3-4-5',
            'address_building' => rand(1, 10) <= 8 ? '古都プラザ404' : null,
            'phone_number' => '03-4567-8901',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.4' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $kotoMemberId = Member::where('user_id', $member->id)->value('id');
        if ($kotoMemberId) {
            $shop = Shop::create([
                'member_id' => $kotoMemberId,
                'shop_name' => 'koto',
                'shop_information' => 'kotoのショップです',
                'shop_introduction' => '古都さんの教材を販売します',
                'receipt_description' => rand(1, 10) <= 8 ? '領収書の発行が可能です' : null,
                'url' => rand(1, 10) <= 8 ? 'https://koto-shop.example.com' : null,
                'total_upload_limit' => rand(1, 10) <= 8 ? 2000000000 : null,
                'transaction_fee_rate' => rand(1, 10) <= 8 ? 0.029 : null,
            ]);
            $this->seedShopIcon($shop->id);
        }

        $member = User::create([
            'id' => 105,
            'name' => '高橋一花',
            'email' => 'donchan@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'donchan',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '一花',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'イチハ',
            'postal_code' => '1000005',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '永田町4-5-6',
            'address_building' => rand(1, 10) <= 8 ? '一花ハウス505' : null,
            'phone_number' => '03-5678-9012',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.5' : null,
        ]);
        // $this->seedMemberIcon($member->id);

        $ichikaMemberId = Member::where('user_id', $member->id)->value('id');
        if ($ichikaMemberId) {
            $shop = Shop::create([
                'member_id' => $ichikaMemberId,
                'shop_name' => 'ichika',
                'shop_information' => 'ichikaのショップです',
                'shop_introduction' => '一花さんの教材を販売します',
                'receipt_description' => rand(1, 10) <= 8 ? '領収書の発行が可能です' : null,
                'url' => rand(1, 10) <= 8 ? 'https://ichika-shop.example.com' : null,
                'total_upload_limit' => rand(1, 10) <= 8 ? 1500000000 : null,
                'transaction_fee_rate' => rand(1, 10) <= 8 ? 0.032 : null,
            ]);
            // $this->seedShopIcon($shop->id);
        }

        $member = User::create([
            'id' => 106,
            'name' => '高橋環奈',
            'email' => 'onigiri@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'onigiri',
            'company' => 0,
            'last_name' => '高橋',
            'first_name' => '環奈',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'カンナ',
            'postal_code' => '1000006',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '有楽町5-6-7',
            'address_building' => rand(1, 10) <= 8 ? '環奈レジデンス606' : null,
            'phone_number' =>  '03-6789-0123',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.6' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $kannaMemberId = Member::where('user_id', $member->id)->value('id');
        if ($kannaMemberId) {
            $shop = Shop::create([
                'member_id' => $kannaMemberId,
                'shop_name' => 'kanna',
                'shop_information' => 'kannaのショップです',
                'shop_introduction' => '環奈さんの教材を販売します',
                'receipt_description' => rand(1, 10) <= 8 ? '領収書の発行が可能です' : null,
                'url' => rand(1, 10) <= 8 ? 'https://kanna-shop.example.com' : null,
                'total_upload_limit' => rand(1, 10) <= 8 ? 1800000000 : null,
                'transaction_fee_rate' => rand(1, 10) <= 8 ? 0.038 : null,
            ]);
            $this->seedShopIcon($shop->id);
        }


        // 法人会員
        $member = User::create([
            'id' => 107,
            'name' => '株式会社ころろ',
            'email' => 'kororo@ayumujuku.com',
            'password' => Hash::make('12345678'),
            'role' => 0,
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        Member::create([
            'user_id' => $member->id,
            'nickname' => 'kororo',
            'company' => 1,
            'last_name' => '高橋',
            'first_name' => '泰宏',
            'last_name_kana' => 'タカハシ',
            'first_name_kana' => 'ヤスヒロ',
            'postal_code' => '1000001',
            'address_prefecture' => '東京都',
            'address_city' => '千代田区',
            'address_block' => '千代田1-1-1',
            'address_building' => rand(1, 10) <= 8 ? '高橋ビル101' : null,
            'phone_number' => '03-1234-5678',
            // 会社情報
            'company_name' => '株式会社ころろ',
            'company_name_kana' => 'カブシキガイシャころろ',
            'company_postal_code' => '1500001',
            'company_prefecture' => '東京都',
            'company_city' => '渋谷区',
            'company_block' => '神宮前1-2-3',
            'company_building' => 'アオゾラビル5F',
            'company_phone_number' => '03-1234-5678',
            'ip_address' => rand(1, 10) <= 8 ? '192.168.1.7' : null,
        ]);
        $this->seedMemberIcon($member->id);

        $kororoMemberId = Member::where('user_id', $member->id)->value('id');
        if ($kororoMemberId) {
            $shop = Shop::create([
                'member_id' => $kororoMemberId,
                'shop_name' => 'kororo',
                'shop_information' => 'kororoの公式ショップです',
                'shop_introduction' => '法人アカウントの教材を販売します',
                'receipt_description' => rand(1, 10) <= 8 ? '法人向け領収書の発行が可能です' : null,
                'url' => rand(1, 10) <= 8 ? 'https://kororo-official.example.com' : null,
                'total_upload_limit' => rand(1, 10) <= 8 ? 5000000000 : null,
                'transaction_fee_rate' => rand(1, 10) <= 8 ? 0.025 : null,
            ]);
            $this->seedShopIcon($shop->id);
        }


    }

    private function seedMemberIcon(int $userId): void
    {
        $directory = "members/{$userId}";
        Storage::disk('public')->makeDirectory($directory);
        $path = $directory . '/member-icon.png';

        // Try to source an icon from assets; fallback to tiny default PNG if none available
        $assetsDir = base_path(self::MEMBER_ICON_ASSETS_DIR);
        $assetFiles = glob($assetsDir . '/*.png') ?: [];
        $assetFiles = array_values(array_filter($assetFiles, 'is_file'));

        if (!empty($assetFiles)) {
            $index = crc32((string) $userId) % count($assetFiles);
            $selectedAssetPath = $assetFiles[$index];
            $png = @file_get_contents($selectedAssetPath);
            if ($png === false) {
                $png = base64_decode(self::DEFAULT_MEMBER_ICON_PNG_BASE64);
            }
        } else {
            $png = base64_decode(self::DEFAULT_MEMBER_ICON_PNG_BASE64);
        }

        // Always (over)write to ensure seeding reflects current assets
        Storage::disk('public')->put($path, $png);
        Member::where('user_id', $userId)->update(['member_icon' => $path]);
    }

    private function seedShopIcon(int $shopId): void
    {
        $directory = "shops/{$shopId}";
        Storage::disk('public')->makeDirectory($directory);
        $path = $directory . '/shop-icon.png';

        $assetsDir = base_path(self::MEMBER_ICON_ASSETS_DIR);
        $assetFiles = glob($assetsDir . '/*.png') ?: [];
        $assetFiles = array_values(array_filter($assetFiles, 'is_file'));

        if (!empty($assetFiles)) {
            $index = crc32((string) $shopId) % count($assetFiles);
            $selectedAssetPath = $assetFiles[$index];
            $png = @file_get_contents($selectedAssetPath);
            if ($png === false) {
                $png = base64_decode(self::DEFAULT_MEMBER_ICON_PNG_BASE64);
            }
        } else {
            $png = base64_decode(self::DEFAULT_MEMBER_ICON_PNG_BASE64);
        }

        Storage::disk('public')->put($path, $png);
        Shop::where('id', $shopId)->update(['shop_icon' => $path]);
    }
}