<?php

namespace Tests\Unit\Support;

use App\Support\SeoMeta;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    #[Test]
    public function canonical_strips_tracking_query_params(): void
    {
        $this->bindRequest('/products', 'products.index', [
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
        ]);

        $this->assertSame(url('/products'), SeoMeta::canonicalUrl());
        $this->assertSame('', SeoMeta::robots());
    }

    #[Test]
    public function canonical_keeps_self_referencing_pagination(): void
    {
        $this->bindRequest('/products', 'products.index', ['page' => 2]);

        $this->assertSame(url('/products').'?page=2', SeoMeta::canonicalUrl());
        $this->assertSame('', SeoMeta::robots());
    }

    #[Test]
    public function canonical_omits_page_one(): void
    {
        $this->bindRequest('/products', 'products.index', ['page' => 1]);

        $this->assertSame(url('/products'), SeoMeta::canonicalUrl());
    }

    #[Test]
    public function category_pagination_is_self_referencing(): void
    {
        $this->bindRequest('/categories/10', 'categories.show', ['page' => 3]);

        $this->assertSame(url('/categories/10').'?page=3', SeoMeta::canonicalUrl());
    }

    #[Test]
    public function product_search_is_noindex_with_clean_canonical(): void
    {
        $this->bindRequest('/products', 'products.index', [
            'q' => '国語',
            'page' => 2,
            'utm_source' => 'x',
        ]);

        $this->assertSame(url('/products'), SeoMeta::canonicalUrl());
        $this->assertSame('noindex', SeoMeta::robots());
    }

    #[Test]
    public function explicit_robots_section_takes_priority(): void
    {
        $this->bindRequest('/products', 'products.index', ['q' => '国語']);

        $this->assertSame('noindex,follow', SeoMeta::robots('noindex,follow'));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function bindRequest(string $path, string $routeName, array $query = []): void
    {
        $request = Request::create(url($path), 'GET', $query);
        $route = new Route(['GET'], ltrim($path, '/'), fn () => null);
        $route->name($routeName);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->app->instance('request', $request);
    }
}
