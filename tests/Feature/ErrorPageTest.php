<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function not_found_page_is_displayed(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('ページが見つかりません')
            ->assertSee('トップページへ戻る');
    }

    #[Test]
    public function admin_area_returns_forbidden_for_non_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('アクセスできません');
    }
}
