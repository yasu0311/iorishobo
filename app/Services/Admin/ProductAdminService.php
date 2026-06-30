<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductAdminService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->create([
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => '__pending__'.uniqid(),
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'base_price' => (int) $data['base_price'],
                'stock_managed' => (bool) ($data['stock_managed'] ?? false),
                'is_published' => (bool) ($data['is_published'] ?? false),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);

            $product->update(['slug' => (string) $product->id]);

            ProductVariant::query()->create([
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->base_price,
                'stock' => 0,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            return $product->fresh(['variants', 'images', 'category']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update([
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'base_price' => (int) $data['base_price'],
            'stock_managed' => (bool) ($data['stock_managed'] ?? false),
            'is_published' => (bool) ($data['is_published'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $product->fresh(['variants', 'images', 'category']);
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                $this->deleteImageFile($image);
            }

            $product->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addVariant(Product $product, array $data): ProductVariant
    {
        return ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $data['name'],
            'attributes' => $this->parseAttributes($data['attributes'] ?? null),
            'price' => (int) $data['price'],
            'stock' => (int) ($data['stock'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? $product->variants()->max('sort_order') + 1),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update([
            'name' => $data['name'],
            'attributes' => $this->parseAttributes($data['attributes'] ?? null),
            'price' => (int) $data['price'],
            'stock' => (int) ($data['stock'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $variant->fresh();
    }

    public function deleteVariant(Product $product, ProductVariant $variant): void
    {
        if ($variant->product_id !== $product->id) {
            throw ValidationException::withMessages([
                'variant' => 'バリアントが見つかりません。',
            ]);
        }

        if ($product->variants()->count() <= 1) {
            throw ValidationException::withMessages([
                'variant' => 'バリアントは最低 1 件必要です。',
            ]);
        }

        $variant->delete();
    }

    public function addImage(Product $product, UploadedFile $file, ?int $sortOrder = null): ProductImage
    {
        $sortOrder ??= $product->images()->count() === 0
            ? 0
            : ((int) $product->images()->max('sort_order')) + 1;

        $path = $file->store("products/{$product->id}", 'public');

        return ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $path,
            'sort_order' => $sortOrder,
        ]);
    }

    public function deleteImage(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw ValidationException::withMessages([
                'image' => '画像が見つかりません。',
            ]);
        }

        $this->deleteImageFile($image);
        $image->delete();
    }

    public function setMainImage(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw ValidationException::withMessages([
                'image' => '画像が見つかりません。',
            ]);
        }

        DB::transaction(function () use ($product, $image) {
            $currentMain = $product->images()->where('sort_order', 0)->where('id', '!=', $image->id)->first();

            if ($currentMain !== null) {
                $currentMain->update(['sort_order' => $image->sort_order]);
            }

            $image->update(['sort_order' => 0]);
        });
    }

    private function deleteImageFile(ProductImage $image): void
    {
        if (! str_starts_with($image->path, 'http://') && ! str_starts_with($image->path, 'https://')) {
            Storage::disk('public')->delete($image->path);
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function parseAttributes(mixed $input): ?array
    {
        if ($input === null || $input === '') {
            return null;
        }

        if (is_array($input)) {
            return $input;
        }

        $decoded = json_decode((string) $input, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'attributes' => '属性は JSON 形式で入力してください。',
            ]);
        }

        return $decoded;
    }
}
