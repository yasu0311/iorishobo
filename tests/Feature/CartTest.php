<?php

namespace Tests\Feature;

use App\Listeners\MergeCartOnLogin;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startSession();

        $category = Category::query()->create([
            'name' => 'テスト',
            'slug' => '1',
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'テスト商品',
            'slug' => '100',
            'base_price' => 1000,
            'stock_managed' => true,
            'is_published' => true,
            'sort_order' => 1,
        ]);

        $this->variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    #[Test]
    public function guest_can_add_item_to_cart(): void
    {
        $response = $this->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('carts', [
            'user_id' => null,
            'session_id' => session()->getId(),
        ]);
    }

    #[Test]
    public function it_rejects_adding_more_than_available_stock(): void
    {
        $response = $this->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 6,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function cart_page_shows_stock_warning_and_blocks_checkout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 5,
        ]);

        $this->variant->update(['stock' => 2]);

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('在庫不足');
        $response->assertDontSee('チェックアウト（準備中）');
    }

    #[Test]
    public function user_can_update_and_remove_cart_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $itemId = Cart::query()->where('user_id', $user->id)->first()->items()->first()->id;

        $this->actingAs($user)->patch(route('cart.items.update', $itemId), ['quantity' => 3])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('cart_items', [
            'id' => $itemId,
            'quantity' => 3,
        ]);

        $this->actingAs($user)->delete(route('cart.items.destroy', $itemId))
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseCount('cart_items', 0);
    }

    #[Test]
    public function coupon_can_be_applied_to_cart(): void
    {
        config(['shop.coupons_enabled' => true]);

        Coupon::query()->create([
            'code' => 'SAVE100',
            'name' => '100円引き',
            'discount_amount' => 100,
            'min_order_amount' => 1000,
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('cart.coupon.apply'), ['coupon_code' => 'SAVE100'])
            ->assertRedirect(route('cart.index'));

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'coupon_id' => Coupon::query()->where('code', 'SAVE100')->value('id'),
        ]);

        $response = $this->actingAs($user)->get(route('cart.index'));
        $response->assertSee('100円引き');
        $response->assertSee('900円');
    }

    #[Test]
    public function coupon_input_is_hidden_when_coupons_disabled(): void
    {
        config(['shop.coupons_enabled' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->get(route('cart.index'))
            ->assertOk()
            ->assertDontSee('クーポンコード')
            ->assertDontSee('合計（割引後）');

        $this->actingAs($user)->post(route('cart.coupon.apply'), ['coupon_code' => 'SAVE100'])
            ->assertNotFound();
    }

    #[Test]
    public function login_merges_guest_cart_into_user_cart(): void
    {
        $this->post(route('cart.items.store'), [
            'variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        $guestSessionId = session()->getId();
        $user = User::factory()->create();

        app(CartService::class)->mergeGuestCartIntoUserCart($user, $guestSessionId);

        $this->assertDatabaseMissing('carts', ['session_id' => $guestSessionId]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'session_id' => null,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function merge_sums_quantities_for_same_variant(): void
    {
        $user = User::factory()->create();

        $userCart = Cart::query()->create(['user_id' => $user->id]);
        $userCart->items()->create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $guestCart = Cart::query()->create(['session_id' => 'guest-session-1']);
        $guestCart->items()->create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
        ]);

        app(CartService::class)->mergeGuestCartIntoUserCart($user, 'guest-session-1');

        $this->assertDatabaseCount('carts', 1);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $userCart->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 3,
        ]);
    }

    #[Test]
    public function login_event_triggers_cart_merge(): void
    {
        Event::fake([Login::class]);

        Event::assertListening(Login::class, MergeCartOnLogin::class);
    }

    #[Test]
    public function cleanup_command_deletes_old_guest_carts(): void
    {
        $oldCart = Cart::query()->create(['session_id' => 'old-session']);
        $oldCart->updated_at = now()->subDays(91);
        $oldCart->saveQuietly();
        $oldCart->items()->create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $recentCart = Cart::query()->create(['session_id' => 'recent-session']);
        $recentCart->updated_at = now()->subDays(10);
        $recentCart->saveQuietly();

        $this->artisan('carts:cleanup-guest')
            ->assertSuccessful();

        $this->assertDatabaseMissing('carts', ['session_id' => 'old-session']);
        $this->assertDatabaseHas('carts', ['session_id' => 'recent-session']);
    }
}
