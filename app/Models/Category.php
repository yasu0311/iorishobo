<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->childrenOrdered();
    }

    public function childrenOrdered(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * 自身とすべての子孫カテゴリの ID（商品一覧の絞り込み用）。
     *
     * @return list<int>
     */
    public function selfAndDescendantIds(): array
    {
        $ids = [$this->id];

        $children = $this->relationLoaded('childrenOrdered')
            ? $this->childrenOrdered
            : $this->childrenOrdered()->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $child->selfAndDescendantIds());
        }

        return $ids;
    }
}
