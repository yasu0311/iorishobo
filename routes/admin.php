<?php

use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/refunds', [OrderController::class, 'storeRefund'])->name('orders.refunds.store');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/variants', [ProductController::class, 'storeVariant'])->name('products.variants.store');
        Route::put('/products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])->name('products.variants.update');
        Route::delete('/products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])->name('products.variants.destroy');
        Route::post('/products/{product}/images', [ProductController::class, 'storeImage'])->name('products.images.store');
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::post('/products/{product}/images/{image}/main', [ProductController::class, 'setMainImage'])->name('products.images.main');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [CouponController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
    });
