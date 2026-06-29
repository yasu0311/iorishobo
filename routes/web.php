<?php

use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\LegacyRedirectController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LegacyRedirectController::class, 'home']);

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/stripe/{order}', [CheckoutController::class, 'stripe'])->name('checkout.stripe');
Route::get('/checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');

Route::post('/webhook/stripe', StripeWebhookController::class)->name('webhook.stripe');
