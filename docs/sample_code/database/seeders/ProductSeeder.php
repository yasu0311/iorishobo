<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    private const VISIBLE_PRODUCT_COUNT = 72;
    private const HIDDEN_PRODUCT_COUNT = 8;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のShopレコードを取得
        $shops = \App\Models\Shop::all();
        
        if ($shops->isEmpty()) {
            $this->command->error('Shopレコードが見つかりません。先にUserSeederを実行してください。');
            return;
        }

        // 一覧確認しやすいよう、表示対象の商品を多めに作る
        $visibleProducts = \App\Models\Product::factory(self::VISIBLE_PRODUCT_COUNT)->create([
            'shop_id' => function () use ($shops) {
                return $shops->random()->id;
            },
            'product_limited' => 0,
            'product_status' => 1,
        ]);

        // 非表示系の商品も少数だけ混ぜて、絞り込み動作を確認できるようにする
        $hiddenProducts = \App\Models\Product::factory(self::HIDDEN_PRODUCT_COUNT)->create([
            'shop_id' => function () use ($shops) {
                return $shops->random()->id;
            },
            'product_limited' => 1,
            'product_status' => 0,
            'price_for_personal' => null,
        ]);

        $products = $visibleProducts->concat($hiddenProducts);

        // 画像アセットの取得（png/jpg/jpeg）
        $assetsDir = base_path('database/seeders/assets/images');
        $assetFiles = glob($assetsDir . '/*.{png,jpg,jpeg,JPG,JPEG,PNG}', GLOB_BRACE) ?: [];
        $assetFiles = array_values(array_filter($assetFiles, 'is_file'));

        // 各商品の画像をストレージに配置し、product_image を更新
        if (!empty($assetFiles)) {
            foreach ($products as $index => $product) {
                $selectedPath = $assetFiles[$index % count($assetFiles)];
                $bytes = @file_get_contents($selectedPath);
                if ($bytes === false) {
                    continue; // 読み込み失敗時はスキップ
                }

                $directory = "shops/{$product->shop_id}/products/{$product->id}";
                Storage::disk('public')->makeDirectory($directory);

                $filename = basename($selectedPath);
                $relativePath = $directory . '/' . $filename;

                // 上書き保存
                Storage::disk('public')->put($relativePath, $bytes);

                // DB 更新（public ディスク基準の相対パスを保存）
                $product->update(['product_image' => $relativePath]);
            }
        } else {
            $this->command->warn('商品画像アセットが見つかりませんでした: database/seeders/assets/images');
        }

        // products_file_typesテーブルにデータを挿入
        $fileTypes = \App\Models\FileType::all();
        
        if ($fileTypes->isEmpty()) {
            $this->command->warn('FileTypeレコードが見つかりません。先にFileTypesSeederを実行してください。');
        } else {
            // 各商品に対して1〜3個のランダムなファイルタイプを紐付け
            foreach ($products as $product) {
                $randomFileTypes = $fileTypes->random(rand(1, 3));
                $product->fileTypes()->attach($randomFileTypes->pluck('id')->toArray());
            }
            $this->command->info('products_file_typesテーブルにデータを追加しました。');
        }
        // products_gradesテーブルにデータを挿入
        $grades = \App\Models\Grade::all();
        if ($grades->isEmpty()) {
            $this->command->warn('Gradeレコードが見つかりません。先にGradesSeederを実行してください。');
        } else {
            foreach ($products as $product) {
                $randomGrades = $grades->random(rand(1, 3));
                $product->grades()->attach($randomGrades->pluck('id')->toArray());
            }
            $this->command->info('products_gradesテーブルにデータを追加しました。');
        }
        // products_subjectsテーブルにデータを挿入
        $subjects = \App\Models\Subject::all();
        if ($subjects->isEmpty()) {
            $this->command->warn('Subjectレコードが見つかりません。先にSubjectsSeederを実行してください。');
        } else {
            foreach ($products as $product) {
                $randomSubjects = $subjects->random(rand(1, 3));
                $product->subjects()->attach($randomSubjects->pluck('id')->toArray());
            }
            $this->command->info('products_subjectsテーブルにデータを追加しました。');
        }

        $this->command->info('表示用商品を多めに含む商品データを正常に作成し、商品画像を設定しました。');
    }
}
