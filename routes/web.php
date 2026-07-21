<?php

use App\Http\Controllers\Front\Auth\ForgotPasswordController;
use App\Http\Controllers\Front\Auth\LoginController;
use App\Http\Controllers\Front\Auth\RegisterController;
use App\Http\Controllers\Front\Auth\ResetPasswordController;
use App\Http\Controllers\Front\Auth\VerifyEmailController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CategoryController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\LegacyRedirectController;
use App\Http\Controllers\Front\Mypage\MypageController;
use App\Http\Controllers\Front\Mypage\OrderController as MypageOrderController;
use App\Http\Controllers\Front\Mypage\ProfileController;
use App\Http\Controllers\Front\Mypage\ReceiptController;
use App\Http\Controllers\Front\ProductController;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\Front\StaticController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LegacyRedirectController::class, 'home'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap.xml');
Route::get('/robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots.txt');

Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contacts.confirm')->middleware('throttle:5,1');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store')->middleware('throttle:5,1');
Route::post('/contacts/back', [ContactController::class, 'back'])->name('contacts.back');
Route::get('/contacts/complete', [ContactController::class, 'complete'])->name('contacts.complete');

Route::prefix('static')->name('static.')->group(function () {
    Route::get('/law', [StaticController::class, 'law'])->name('law');
    Route::get('/privacy-policy', [StaticController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('/terms', [StaticController::class, 'terms'])->name('terms');
});

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
Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::post('/checkout/back', [CheckoutController::class, 'back'])->name('checkout.back');
Route::post('/checkout/edit-cart', [CheckoutController::class, 'editCart'])->name('checkout.edit-cart');
Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
Route::post('/checkout/resume/{order}', [CheckoutController::class, 'resume'])->name('checkout.resume');
Route::get('/checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');

Route::post('/webhook/stripe', StripeWebhookController::class)->name('webhook.stripe');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.store');
});

Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [VerifyEmailController::class, 'send'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('mypage.profile.edit');
    Route::put('/mypage/profile', [ProfileController::class, 'update'])->name('mypage.profile.update');
    Route::get('/mypage/orders', [MypageOrderController::class, 'index'])->name('mypage.orders.index');
    Route::get('/mypage/orders/{order}', [MypageOrderController::class, 'show'])->name('mypage.orders.show');
    Route::get('/mypage/orders/{order}/receipt', [ReceiptController::class, 'show'])->name('mypage.orders.receipt');
});
