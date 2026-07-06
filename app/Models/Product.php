<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function formattedPrice(): ?string
    {
        $lowest = $this->lowestPrice();

        if ($lowest === null) {
            return null;
        }

        $highest = $this->highestPrice();

        if ($highest === null || $lowest === $highest) {
            return number_format($lowest).'円';
        }

        return number_format($lowest).'円〜'.number_format($highest).'円';
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
