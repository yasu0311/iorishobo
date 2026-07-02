<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $items = array_merge(
            $this->buildStaticItems(),
            $this->buildCategoryItems(),
            $this->buildProductItems(),
        );

        $xml = view('sitemap.xml', compact('items'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function buildStaticItems(): array
    {
        $now = Carbon::now()->toAtomString();
        $latestProductUpdatedAt = Product::query()->published()->max('updated_at');
        $productFallback = $latestProductUpdatedAt
            ? Carbon::parse($latestProductUpdatedAt)->toAtomString()
            : $now;

        return [
            [
                'loc' => route('home'),
                'lastmod' => $this->resolveLastmod(['views/front/home/index.blade.php'], $productFallback),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('products.index'),
                'lastmod' => $this->resolveLastmod(['views/front/products/index.blade.php'], $productFallback),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => route('categories.index'),
                'lastmod' => $this->resolveLastmod(['views/front/categories/index.blade.php'], $productFallback),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => route('static.privacy-policy'),
                'lastmod' => $this->resolveLastmod(['views/front/static/privacy-policy.blade.php'], $now),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('static.terms'),
                'lastmod' => $this->resolveLastmod(['views/front/static/terms.blade.php'], $now),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ],
            [
                'loc' => route('contacts.create'),
                'lastmod' => $this->resolveLastmod(['views/front/contact/create.blade.php'], $now),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ],
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function buildCategoryItems(): array
    {
        return Category::query()
            ->ordered()
            ->select(['id', 'slug', 'updated_at'])
            ->get()
            ->map(function (Category $category): array {
                return [
                    'loc' => route('categories.show', $category->slug),
                    'lastmod' => optional($category->updated_at)->toAtomString() ?? Carbon::now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            })
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function buildProductItems(): array
    {
        return Product::query()
            ->published()
            ->select(['id', 'slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Product $product): array {
                return [
                    'loc' => route('products.show', $product->slug),
                    'lastmod' => optional($product->updated_at)->toAtomString() ?? Carbon::now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            })
            ->all();
    }

    private function resolveLastmod(array $relativePaths, string $fallback): string
    {
        $timestamps = collect($relativePaths)
            ->map(fn (string $path) => resource_path($path))
            ->filter(fn (string $path) => is_file($path))
            ->map(fn (string $path) => filemtime($path))
            ->filter(fn ($timestamp) => $timestamp !== false);

        if ($timestamps->isEmpty()) {
            return $fallback;
        }

        return Carbon::createFromTimestamp(max($timestamps->all()))->toAtomString();
    }
}
