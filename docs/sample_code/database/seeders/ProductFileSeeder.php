<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のProductレコードを取得
        $products = \App\Models\Product::all();
        
        if ($products->isEmpty()) {
            $this->command->error('Productレコードが見つかりません。先にProductSeederを実行してください。');
            return;
        }

        // ドキュメントアセットの取得
        $assetsDir = base_path('database/seeders/assets/documents');
        $assetFiles = glob($assetsDir . '/*.{pdf,PDF}', GLOB_BRACE) ?: [];
        $assetFiles = array_values(array_filter($assetFiles, 'is_file'));

        if (empty($assetFiles)) {
            $this->command->warn('ドキュメントアセットが見つかりませんでした: database/seeders/assets/documents');
            return;
        }

        $this->command->info('商品ファイルを設定中...');

        // 各商品に対してファイルを設定
        foreach ($products as $product) {
            // 各商品に3-5個のファイルを設定
            $fileCount = rand(3, 5);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $selectedPath = $assetFiles[$i % count($assetFiles)];
                $bytes = @file_get_contents($selectedPath);
                
                if ($bytes === false) {
                    continue; // 読み込み失敗時はスキップ
                }

                // ファイル情報を取得
                $originalFilename = basename($selectedPath);
                $fileSize = strlen($bytes);
                $fileExtension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                
                // ファイル名を英数字で生成（拡張子は除く）
                $asciiName = strtolower(\Illuminate\Support\Str::random(16));
                if ($i > 0) {
                    $asciiName .= '_' . ($i + 1);
                }

                // ストレージパス設定
                $directory = "shops/{$product->shop_id}/products/{$product->id}/files";
                Storage::disk('private')->makeDirectory($directory);

                $filename = $asciiName . '.' . $fileExtension;
                $relativePath = $directory . '/' . $filename;

                // ファイルをprivateストレージに保存
                Storage::disk('private')->put($relativePath, $bytes);

                // ProductFileレコードを作成
                \App\Models\ProductFile::create([
                    'product_id' => $product->id,
                    'sample' => $i === 0 ? 1 : 0, // 最初のファイルは見本、他は商品
                    'file_name' => $asciiName,
                    'file_path' => $relativePath,
                    'file_size' => $fileSize,
                    'file_description' => "教育教材ファイルの詳細説明です。教育現場で活用できる教材として制作されています。",
                    'copyright' => '© 2024 教育教材販売',
                    'macro' => 'マクロ機能は使用していません',
                    'file_updated_at' => now(),
                    'security_check' => 1, // 済
                    'display_order' => rand(1, 10) <= 8 ? $i + 1 : null,
                    'ip_address' => rand(1, 10) <= 8 ? '127.0.0.1' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('商品ファイルを正常に設定しました。');
    }
}
