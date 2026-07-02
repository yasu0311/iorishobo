<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\ProductFile;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class ProductFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'sample' => 'required|integer|in:0,1',
            'file_name' => 'required|string|max:100',
            'file_description' => 'required|string|max:1000',
            'copyright' => 'nullable|string|max:1000',
            'macro' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0',
        ];

        // 設定値から1ファイルあたりの最大サイズを取得（デフォルトは100MB）
        $singleFileLimitBytes = Setting::getValue('single_file_upload_limit');
        $defaultLimitKilobytes = 102400; // 100MB
        $maxKilobytes = $defaultLimitKilobytes;

        if (is_numeric($singleFileLimitBytes) && (int)$singleFileLimitBytes > 0) {
            $maxKilobytes = (int) ceil(((int)$singleFileLimitBytes) / 1024);
        }

        $allowedExtensions = config('product-file.allowed_extensions', []);

        // 作成時はファイルが必須、更新時は任意。拡張子は許可リストで検証
        $fileRules = [
            $this->isMethod('post') ? 'required' : 'nullable',
            'file',
            'mimetypes:application/pdf,text/plain,application/zip,application/x-rar-compressed,application/vnd.rar,application/x-7z-compressed,application/x-tar,application/gzip,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-word.document.macroEnabled.12,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel.sheet.macroEnabled.12,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/vnd.ms-powerpoint.presentation.macroEnabled.12,image/jpeg,image/png,image/gif,image/bmp,image/svg+xml,image/webp,image/tiff,image/x-icon,image/heic,image/avif',
            'max:' . $maxKilobytes,
            function (string $attribute, $value, \Closure $fail) use ($allowedExtensions): void {
                if (!$value || !$value->isValid()) {
                    return;
                }
                $ext = strtolower($value->getClientOriginalExtension());
                if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
                    $fail('アップロード可能な形式は' . config('product-file.allowed_extensions_description', '設定を確認してください') . 'のみです。');
                }
            },
        ];
        $rules['product_file'] = $fileRules;

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $product = $this->route('product');
            if (! $product instanceof Product) {
                return;
            }

            if ($this->isMethod('POST')) {
                $maxFiles = Setting::getValue('product_files_limit', $product->shop_id);
                if (is_numeric($maxFiles) && (int) $maxFiles >= 1) {
                    $maxFiles = (int) $maxFiles;
                    $current = $product->productFiles()->count();
                    if ($current >= $maxFiles) {
                        $validator->errors()->add(
                            'product_file',
                            "1商品あたり登録できるファイルは{$maxFiles}件までです。"
                        );
                    }
                }
            }

            $this->validateShopTotalUploadCapacity($validator, $product);
        });
    }

    /**
     * ショップ全体の total_upload_limit を超えないよう検証（新規登録・ファイル差し替え時）。
     */
    private function validateShopTotalUploadCapacity($validator, Product $product): void
    {
        $uploaded = $this->file('product_file');
        if (! $uploaded || ! $uploaded->isValid()) {
            return;
        }

        $shop = $product->shop;
        if (! $shop) {
            return;
        }

        $cap = $shop->totalUploadBytesCapFromSettings();
        if ($cap === null) {
            return;
        }

        $newBytes = (int) $uploaded->getSize();
        $used = $shop->totalProductFilesBytes();

        if ($this->isMethod('POST')) {
            $projected = $used + $newBytes;
        } elseif ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $existing = $this->route('file');
            if (! $existing instanceof ProductFile || (int) $existing->product_id !== (int) $product->id) {
                return;
            }
            $projected = $used - (int) $existing->file_size + $newBytes;
        } else {
            return;
        }

        if ($projected > $cap) {
            $capMb = max(1, (int) round($cap / 1024 / 1024));
            $validator->errors()->add(
                'product_file',
                "ショップ全体のアップロード容量上限（{$capMb}MB）を超えるため、このファイルは登録できません。"
            );
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $singleFileLimitBytes = Setting::getValue('single_file_upload_limit');
        $defaultLimitBytes = 104857600; // 100MB
        $maxBytes = (is_numeric($singleFileLimitBytes) && (int) $singleFileLimitBytes > 0)
            ? (int) $singleFileLimitBytes
            : $defaultLimitBytes;
        $maxMb = (int) round($maxBytes / 1024 / 1024);

        return [
            'sample.required' => '見本/商品の選択は必須です。',
            'sample.in' => '見本/商品の選択が不正です。',
            'file_name.required' => '商品ファイル名は必須です。',
            'file_name.max' => '商品ファイル名は100文字以内で入力してください。',
            'file_description.required' => 'ファイル説明は必須です。',
            'file_description.max' => 'ファイル説明は1000文字以内で入力してください。',
            'copyright.max' => '著作権に関する事項は1000文字以内で入力してください。',
            'macro.max' => 'プログラムファイルに関する事項は1000文字以内で入力してください。',
            'product_file.required' => 'ファイルのアップロードは必須です。',
            'product_file.file' => 'アップロードされたファイルが無効です。',
            'product_file.mimetypes' => 'ファイル形式が許可されていません。',
            'product_file.max' => "ファイルサイズは{$maxMb}MB以内でアップロードしてください。",
            'display_order.integer' => '表示順は数値で入力してください。',
            'display_order.min' => '表示順は0以上で入力してください。',
        ];
    }
}
