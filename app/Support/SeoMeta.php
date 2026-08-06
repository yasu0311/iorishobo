<?php

namespace App\Support;

use Illuminate\Http\Request;

class SeoMeta
{
    /**
     * robots メタの値を決定する。
     *
     * ビューの @section('robots') があれば優先。なければ検索結果・
     * プライベート系ルートに noindex を付与する。
     */
    public static function robots(string $sectionRobots = '', ?Request $request = null): string
    {
        $sectionRobots = trim($sectionRobots);
        if ($sectionRobots !== '') {
            return $sectionRobots;
        }

        $request ??= request();
        $routeName = $request->route()?->getName() ?? '';

        if (self::isProductSearch($request, $routeName)) {
            return 'noindex';
        }

        if ($routeName !== '' && in_array($routeName, config('shop.noindex_route_names', []), true)) {
            return 'noindex';
        }

        foreach (config('shop.noindex_route_prefixes', []) as $prefix) {
            if ($routeName !== '' && str_starts_with($routeName, $prefix)) {
                return 'noindex';
            }
        }

        return '';
    }

    /**
     * canonical / og:url 用の正規化 URL。
     *
     * - 検索結果（?q=）: クエリなしの一覧 URL
     * - ページネーション（page>=2）: ?page=N のみ残すセルフ参照
     * - UTM 等のその他クエリ: 捨てる
     */
    public static function canonicalUrl(?Request $request = null): string
    {
        $request ??= request();
        $routeName = $request->route()?->getName() ?? '';
        $canonical = url()->current();

        if (self::isProductSearch($request, $routeName)) {
            return $canonical;
        }

        $page = (int) $request->query('page', 1);
        if ($page >= 2) {
            return $canonical.'?'.http_build_query(['page' => $page]);
        }

        return $canonical;
    }

    private static function isProductSearch(Request $request, string $routeName): bool
    {
        return $routeName === 'products.index' && $request->filled('q');
    }
}
