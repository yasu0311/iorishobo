<?php

namespace App\Services\Colorme;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryResolver
{
    /**
     * 大/小カテゴリー名から category_id を解決する。作成時は slug = id（§3.21）。
     */
    public function resolve(?string $parentName, ?string $childName): ?int
    {
        $parentName = $this->normalize($parentName);
        $childName = $this->normalize($childName);

        if ($parentName === null && $childName === null) {
            return null;
        }

        if ($parentName === null) {
            return $this->findOrCreateCategory($childName, null);
        }

        $parentId = $this->findOrCreateCategory($parentName, null);

        if ($childName === null) {
            return $parentId;
        }

        return $this->findOrCreateCategory($childName, $parentId);
    }

    private function normalize(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : $name;
    }

    private function findOrCreateCategory(string $name, ?int $parentId): int
    {
        $existing = Category::query()
            ->where('name', $name)
            ->where('parent_id', $parentId)
            ->first();

        if ($existing !== null) {
            return $existing->id;
        }

        return DB::transaction(function () use ($name, $parentId): int {
            $category = Category::query()->create([
                'name' => $name,
                'parent_id' => $parentId,
                'slug' => '0',
                'sort_order' => 0,
            ]);

            $category->update(['slug' => (string) $category->id]);

            return $category->id;
        });
    }
}
