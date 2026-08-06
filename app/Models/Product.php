<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'colorme_product_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'base_price',
        'stock_managed',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'stock_managed' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeMatchingKeyword(Builder $query, string $keyword): Builder
    {
        return $query->where(function (Builder $builder) use ($keyword) {
            $builder->where('name', 'like', "%{$keyword}%")
                ->orWhere('short_description', 'like', "%{$keyword}%");
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('sort_order', 0);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function hasPurchasableVariant(): bool
    {
        return $this->activeVariants
            ->each(fn (ProductVariant $variant) => $variant->setRelation('product', $this))
            ->contains(fn (ProductVariant $variant) => $variant->isPurchasable());
    }

    public function lowestPrice(): ?int
    {
        $prices = $this->activeVariantPrices();

        return $prices?->min();
    }

    public function highestPrice(): ?int
    {
        $prices = $this->activeVariantPrices();

        return $prices?->max();
    }

    /**
     * 店頭表示用の税込最安値。
     */
    public function lowestPriceInclusive(?ConsumptionTax $tax = null): ?int
    {
        $lowest = $this->lowestPrice();

        if ($lowest === null) {
            return null;
        }

        return ($tax ?? ConsumptionTax::current())->inclusiveFromExclusive($lowest);
    }

    /**
     * 店頭表示用の税込最高値。
     */
    public function highestPriceInclusive(?ConsumptionTax $tax = null): ?int
    {
        $highest = $this->highestPrice();

        if ($highest === null) {
            return null;
        }

        return ($tax ?? ConsumptionTax::current())->inclusiveFromExclusive($highest);
    }

    /**
     * 店頭表示用の税込価格文字列（例: "2,200円" / "1,650円〜2,200円"）。
     */
    public function formattedPrice(): ?string
    {
        $tax = ConsumptionTax::current();
        $lowest = $this->lowestPriceInclusive($tax);

        if ($lowest === null) {
            return null;
        }

        $highest = $this->highestPriceInclusive($tax);

        if ($highest === null || $lowest === $highest) {
            return number_format($lowest).'円';
        }

        return number_format($lowest).'円〜'.number_format($highest).'円';
    }

    /**
     * schema.org Product 用の構造化データ（JSON-LD）。
     *
     * 価格は店頭と同じ税込。在庫・購入可否は offers.availability に反映する。
     *
     * @return array<string, mixed>
     */
    public function toJsonLd(): array
    {
        $url = route('products.show', $this->slug);
        $description = Str::limit(strip_tags((string) ($this->short_description ?: $this->name)), 5000);

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $this->name,
            'description' => $description,
            'url' => $url,
            'brand' => [
                '@type' => 'Brand',
                'name' => config('shop.name'),
            ],
        ];

        $images = $this->jsonLdImages();
        if ($images !== []) {
            $data['image'] = count($images) === 1 ? $images[0] : $images;
        }

        $offers = $this->jsonLdOffers($url);
        if ($offers !== null) {
            $data['offers'] = $offers;
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function jsonLdImages(): array
    {
        $images = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        return $images
            ->map(fn (ProductImage $image) => url($image->url()))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jsonLdOffers(string $url): ?array
    {
        $tax = ConsumptionTax::current();
        $low = $this->lowestPriceInclusive($tax);
        $high = $this->highestPriceInclusive($tax);

        if ($low === null) {
            return null;
        }

        $availability = $this->hasPurchasableVariant()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $seller = [
            '@type' => 'Organization',
            'name' => config('shop.name'),
        ];

        if ($high !== null && $high !== $low) {
            $variants = $this->relationLoaded('activeVariants')
                ? $this->activeVariants
                : $this->activeVariants()->get(['id']);

            return [
                '@type' => 'AggregateOffer',
                'url' => $url,
                'priceCurrency' => 'JPY',
                'lowPrice' => (string) $low,
                'highPrice' => (string) $high,
                'offerCount' => $variants->count(),
                'availability' => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
                'seller' => $seller,
            ];
        }

        return [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'JPY',
            'price' => (string) $low,
            'availability' => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => $seller,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>|null
     */
    private function activeVariantPrices(): ?\Illuminate\Support\Collection
    {
        $variants = $this->relationLoaded('activeVariants')
            ? $this->activeVariants
            : $this->activeVariants()->get(['price']);

        if ($variants->isEmpty()) {
            return null;
        }

        return $variants->pluck('price')->map(fn ($price) => (int) $price);
    }
}
