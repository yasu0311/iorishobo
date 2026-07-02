<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StaticPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function law_page_is_accessible(): void
    {
        $this->get(route('static.law'))
            ->assertOk()
            ->assertSee('特定商取引法に基づく表記')
            ->assertSee(config('shop.name'), false);
    }

    #[Test]
    public function privacy_policy_page_is_accessible(): void
    {
        $this->get(route('static.privacy-policy'))
            ->assertOk()
            ->assertSee('プライバシーポリシー');
    }

    #[Test]
    public function terms_page_is_accessible(): void
    {
        $this->get(route('static.terms'))
            ->assertOk()
            ->assertSee('利用規約');
    }

    #[Test]
    public function home_page_includes_footer_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('static.law'), false)
            ->assertSee(route('contacts.create'), false);
    }
}
