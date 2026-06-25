<?php

namespace App\Services\Colorme;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageDownloader
{
    public function __construct(
        private readonly ?ImportLogger $logger = null,
    ) {}

    /**
     * @return array{downloaded: int, skipped: int, errors: int, log_path: string|null}
     */
    public function downloadAll(): array
    {
        $logger = $this->logger ?? new ImportLogger('product-images');
        $downloaded = 0;
        $errors = 0;

        ProductImage::query()
            ->where(function ($query): void {
                $query->where('path', 'like', 'http://%')
                    ->orWhere('path', 'like', 'https://%');
            })
            ->orderBy('id')
            ->each(function (ProductImage $image) use ($logger, &$downloaded, &$errors): void {
                try {
                    if ($this->downloadImage($image)) {
                        $downloaded++;
                        $logger->imported();
                    } else {
                        $logger->skipped($image->id, 'URL が空または無効です', ['path' => $image->path]);
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $logger->error($image->id, $e->getMessage(), ['path' => $image->path]);
                }
            });

        $summary = $logger->finish();
        $summary['downloaded'] = $downloaded;

        return $summary;
    }

    public function downloadImage(ProductImage $image): bool
    {
        $url = trim($image->path);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}: {$url}");
        }

        $extension = $this->guessExtension($url, $response->header('Content-Type'));
        $filename = $image->id.'_'.Str::random(8).'.'.$extension;
        $relativePath = 'products/'.$image->product_id.'/'.$filename;

        Storage::disk('public')->put($relativePath, $response->body());

        $image->update(['path' => $relativePath]);

        return true;
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        return match ($contentType) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
