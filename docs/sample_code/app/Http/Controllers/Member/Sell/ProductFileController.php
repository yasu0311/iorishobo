<?php

namespace App\Http\Controllers\Member\Sell;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductFileRequest;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class ProductFileController extends Controller
{
    /**
     * アプリ設定と php.ini の upload_max_filesize / post_max_size のうち実効的な上限（バイト）
     *
     * @return array{effective_max_bytes: int, upload_max_mb: int}
     */
    private function effectiveProductFileUploadLimits(): array
    {
        $singleFileLimitBytes = Setting::getValue('single_file_upload_limit');
        $defaultLimitBytes = 104857600;
        $appMaxBytes = (is_numeric($singleFileLimitBytes) && (int) $singleFileLimitBytes > 0)
            ? (int) $singleFileLimitBytes
            : $defaultLimitBytes;
        $iniMaxBytes = SymfonyUploadedFile::getMaxFilesize();
        $iniCap = is_float($iniMaxBytes) ? (int) min($iniMaxBytes, PHP_INT_MAX) : $iniMaxBytes;
        $effectiveMaxBytes = (int) min($appMaxBytes, $iniCap);
        $uploadMaxMb = max(1, (int) round($effectiveMaxBytes / 1024 / 1024));

        return [
            'effective_max_bytes' => $effectiveMaxBytes,
            'upload_max_mb' => $uploadMaxMb,
        ];
    }

    public function create(Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($product->shop_id !== $shop->id) {
            abort(403, 'この商品のファイルを登録する権限がありません。');
        }

        $limits = $this->effectiveProductFileUploadLimits();
        $uploadMaxMb = $limits['upload_max_mb'];
        $clientMaxProductFileBytes = $limits['effective_max_bytes'];
        $clientProductFileOversizedMessage =
            'ファイルサイズは'.$uploadMaxMb.'MB以内でアップロードしてください。';

        return view('member.sell.product-files.create', compact(
            'product',
            'uploadMaxMb',
            'clientMaxProductFileBytes',
            'clientProductFileOversizedMessage',
        ));
    }

    public function store(ProductFileRequest $request, Product $product)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($product->shop_id !== $shop->id) {
            abort(403, 'この商品のファイルを登録する権限がありません。');
        }

        $validated = $request->validated();

        // ファイルアップロード処理
        if ($request->hasFile('product_file')) {
            // ディレクトリを作成
            $directory = "shops/{$shop->id}/products/{$product->id}/files";
            Storage::disk('private')->makeDirectory($directory, 0755, true);

            // ファイルを保存（ストレージ上の名前は英数字のみ）
            $file = $request->file('product_file');
            $filename = ProductFile::generateStorageFilename($file->getClientOriginalExtension());
            $path = $file->storeAs($directory, $filename, 'private');
            $fileSize = $file->getSize();
            $fileUpdatedAt = now();
        } else {
            abort(400, 'ファイルがアップロードに失敗しました。失敗が続くようであれば、サイト管理者にお問い合わせください。');
        }

        // 商品ファイルを作成
        ProductFile::create([
            'product_id' => $product->id,
            'sample' => $validated['sample'],
            'file_name' => $validated['file_name'],
            'file_path' => $path,
            'file_size' => $fileSize,
            'file_description' => $validated['file_description'],
            'copyright' => $validated['copyright'],
            'macro' => $validated['macro'],
            'file_updated_at' => $fileUpdatedAt,
            'security_check' => 0, // デフォルトで未チェック
            'display_order' => $validated['display_order'] ?? null,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('member.sell.products.edit', $product)->with('success', '商品ファイル「'.$validated['file_name'].'」を新規登録しました。');
    }

    public function edit(Product $product, ProductFile $file)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($file->product->shop_id !== $shop->id || $file->product_id !== $product->id) {
            abort(403, 'この商品ファイルを編集する権限がありません。');
        }

        $limits = $this->effectiveProductFileUploadLimits();
        $uploadMaxMb = $limits['upload_max_mb'];
        $clientMaxProductFileBytes = $limits['effective_max_bytes'];
        $clientProductFileOversizedMessage =
            'ファイルサイズは'.$uploadMaxMb.'MB以内でアップロードしてください。';

        return view('member.sell.product-files.edit', compact(
            'product',
            'file',
            'uploadMaxMb',
            'clientMaxProductFileBytes',
            'clientProductFileOversizedMessage',
        ));
    }

    public function update(ProductFileRequest $request, Product $product, ProductFile $file)
    {
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($file->product->shop_id !== $shop->id || $file->product_id !== $product->id) {
            abort(403, 'この商品ファイルを編集する権限がありません。');
        }

        $validated = $request->validated();

        // ファイルアップロード処理
        $path = $file->file_path;
        $fileSize = $file->file_size;
        $fileUpdatedAt = $file->file_updated_at;
        $fileReplaced = $request->hasFile('product_file');

        if ($fileReplaced) {
            // 古いファイルを削除
            if ($file->file_path && Storage::disk('private')->exists($file->file_path)) {
                Storage::disk('private')->delete($file->file_path);
            }

            // ディレクトリを作成
            $directory = "shops/{$shop->id}/products/{$product->id}/files";
            Storage::disk('private')->makeDirectory($directory, 0755, true);

            // 新しいファイルを保存（ストレージ上の名前は英数字のみ）
            $uploadedFile = $request->file('product_file');
            $filename = ProductFile::generateStorageFilename($uploadedFile->getClientOriginalExtension());
            $path = $uploadedFile->storeAs($directory, $filename, 'private');
            $fileSize = $uploadedFile->getSize();
            $fileUpdatedAt = now();
        }

        // 商品ファイルを更新（IPはファイル差し替え時のみ記録）
        $updateData = [
            'sample' => $validated['sample'],
            'file_name' => $validated['file_name'],
            'file_path' => $path,
            'file_size' => $fileSize,
            'file_description' => $validated['file_description'],
            'copyright' => $validated['copyright'],
            'macro' => $validated['macro'],
            'file_updated_at' => $fileUpdatedAt,
            'display_order' => $validated['display_order'] ?? null,
        ];

        if ($fileReplaced) {
            $updateData['ip_address'] = $request->ip();
        }

        $file->update($updateData);

        return redirect()->route('member.sell.products.edit', $product)->with('success', '商品ファイル「'.$validated['file_name'].'」を更新しました。');
    }

    public function destroy(Product $product, ProductFile $file)
    {
        // 商品ファイルの所属商品が本人のものであることを認証
        $user = Auth::user();
        $member = $user->member;
        $shop = $member->shop;
        if ($file->product->shop_id !== $shop->id || $file->product_id !== $product->id) {
            abort(403, 'この商品ファイルを削除する権限がありません。');
        }

        // ファイルを削除
        if ($file->file_path && Storage::disk('private')->exists($file->file_path)) {
            Storage::disk('private')->delete($file->file_path);
        }

        // データベースから削除
        $fileName = $file->file_name;
        $file->delete();

        // ファイル数が0になったら「準備中に変更」
        if ($product->productFiles()->count() == 0) {
            $product->product_status = 0;
            $product->save();

            return redirect()->route('member.sell.products.edit', $product)->with('success', '商品ファイル「'.$fileName.'」を削除しました。')->with('info', '商品ファイルをすべて削除したため，商品の状態を「準備中」に変更しました。');
        } else {
            return redirect()->route('member.sell.products.edit', $product)->with('success', '商品ファイル「'.$fileName.'」を削除しました。');
        }
    }
}
