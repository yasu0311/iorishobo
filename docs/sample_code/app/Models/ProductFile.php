<?php

namespace App\Models;

use App\Models\Concerns\HasPublicNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductFile extends Model
{
    use HasFactory, HasPublicNumber;

    protected $table = 'products_files';

    public function getRouteKeyName(): string
    {
        return 'file_number';
    }

    protected $fillable = [
        'file_number',
        'product_id',
        'sample',
        'file_name',
        'file_path',
        'file_size',
        'file_description',
        'copyright',
        'macro',
        'file_updated_at',
        'security_check',
        'display_order',
        'ip_address'
    ];

    // キャスト
    protected $casts = [
        'product_id' => 'integer',
        'sample' => 'boolean',
        'file_size' => 'integer',
        'file_updated_at' => 'datetime',
        'security_check' => 'boolean',
        'display_order' => 'integer',
    ];

    // リレーション
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // クエリスコープ
    // display_orderで並び替え（NULL値は最後に配置）
    public function scopeOrderedByDisplay($query)
    {
        return $query->orderByRaw('display_order IS NULL, display_order ASC')
                     ->orderBy('created_at', 'desc');
    }

    // アクセサ
    public function getSampleTextAttribute()
    {
        return match($this->sample) {
            0 => '商品',
            1 => '見本',
        };        
    }
    public function getSecurityCheckTextAttribute()
    {
        return match($this->security_check) {
            0 => '未',
            1 => '済',
        };
    }
    // 日本語訳
    public function attributes()
    {
        return [
            'product_id' => '商品ID',
            'sample' => '見本',
            'file_name' => 'ファイル名',
            'file_path' => 'ファイルパス',
            'file_size' => 'ファイルサイズ',
            'file_description' => 'ファイル説明',
            'copyright' => '著作権',
            'macro' => 'マクロ',
            'file_updated_at' => 'ファイル更新日時',
            'security_check' => 'セキュリティーチェック',
            'display_order' => '表示順',
            'ip_address' => 'IPアドレス',
        ];
    }
    /**
     * private ストレージ用のファイル名（英数字 + 拡張子）を生成する。
     */
    public static function generateStorageFilename(string $extension): string
    {
        $extension = strtolower(ltrim($extension, '.'));

        if ($extension === '') {
            return strtolower(Str::random(16));
        }

        return strtolower(Str::random(16)).'.'.$extension;
    }

    /**
     * ダウンロード時にブラウザへ渡すファイル名（file_name ベース、拡張子は file_path から補完）。
     */
    public function getDownloadFilenameAttribute(): string
    {
        $displayName = $this->sanitizeDownloadBaseName((string) $this->file_name);
        $storageExtension = strtolower(pathinfo((string) $this->file_path, PATHINFO_EXTENSION));

        if ($displayName === '') {
            return basename((string) $this->file_path);
        }

        $displayExtension = strtolower(pathinfo($displayName, PATHINFO_EXTENSION));

        if ($displayExtension !== '') {
            return $displayName;
        }

        if ($storageExtension !== '') {
            return $displayName.'.'.$storageExtension;
        }

        return $displayName;
    }

    public function downloadResponse(): BinaryFileResponse
    {
        return response()->download(
            Storage::disk('private')->path($this->file_path),
            $this->download_filename,
        );
    }

    private function sanitizeDownloadBaseName(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return '';
        }

        return preg_replace('/[\\\\\\/:*?"<>|]/u', '_', $trimmed) ?? $trimmed;
    }

    // ビジネスロジック
}
