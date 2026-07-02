<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * 公開ページ向けの最小サイトマップを返す。
     */
    public function __invoke(): Response
    {
        $items = array_merge(
            $this->buildStaticItems(),
            $this->buildProductItems()
        );

        $xml = view('sitemap.xml', compact('items'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * 固定ページのサイトマップ項目を作成する。
     *
     * @return array<int, array{loc:string,lastmod:string,changefreq:string,priority:string}>
     */
    private function buildStaticItems(): array
    {
        $now = Carbon::now()->toAtomString();
        $latestProductUpdatedAt = Product::query()->available()->max('updated_at');
        $latestShopUpdatedAt = Shop::query()->available()->max('updated_at');

        return [
            [
                'loc' => route('home'),
                'lastmod' => $this->resolveLastmod(['index.blade.php'], $latestProductUpdatedAt ?? $now),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => route('member.buy.products.index'),
                'lastmod' => $this->resolveLastmod(['member/buy/products/index.blade.php'], $latestProductUpdatedAt ?? $now),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => route('member.buy.shops.index'),
                'lastmod' => $this->resolveLastmod(['member/buy/shops/index.blade.php'], $latestShopUpdatedAt ?? $now),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            ['loc' => route('static.faq'), 'lastmod' => $this->resolveLastmod(['static/faq.blade.php'], $now), 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => route('static.fee'), 'lastmod' => $this->resolveLastmod(['static/fee.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('static.how-to-buy'), 'lastmod' => $this->resolveLastmod(['static/how-to-buy.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('static.how-to-sell'), 'lastmod' => $this->resolveLastmod(['static/how-to-sell.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('static.privacy-policy'), 'lastmod' => $this->resolveLastmod(['static/privacy-policy.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('static.terms'), 'lastmod' => $this->resolveLastmod(['static/terms.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('contacts.create'), 'lastmod' => $this->resolveLastmod(['contact/create.blade.php'], $now), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];
    }

    /**
     * 公開中商品のサイトマップ項目を作成する。
     *
     * @return array<int, array{loc:string,lastmod:string,changefreq:string,priority:string}>
     */
    private function buildProductItems(): array
    {
        return Product::query()
            ->available()
            ->select(['id', 'product_number', 'updated_at'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Product $product): array {
                return [
                    'loc' => route('member.buy.products.show', $product),
                    'lastmod' => optional($product->updated_at)->toAtomString() ?? Carbon::now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            })
            ->all();
    }

    /**
     * Bladeファイル群の最終更新日時をlastmodとして返す。
     */
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
