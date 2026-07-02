<?php

use App\Http\Controllers\Member\IndexController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\WithdrawalController;
use App\Http\Controllers\Member\PassbookController;
use App\Http\Controllers\Member\Buy\ProductController as BuyProductController;
use App\Http\Controllers\Member\Buy\OrderController as BuyOrderController;
use App\Http\Controllers\Member\Buy\CheckoutController;
use App\Http\Controllers\Member\Buy\ShopController as BuyShopController;
use App\Http\Controllers\Member\Sell\ProductController as SellProductController;
use App\Http\Controllers\Member\Sell\ShopController;
use App\Http\Controllers\Member\Buy\FavoriteController;
use App\Http\Controllers\Member\Sell\ProductFileController;
use App\Http\Controllers\Member\Sell\SaleController;
use App\Http\Controllers\Member\MessageController;
use App\Http\Controllers\Member\MessageBoxController;
use App\Http\Controllers\Member\MessageReplyController;
use App\Http\Controllers\Member\ReviewController;
use App\Http\Controllers\Member\ReviewReplyController;
use App\Http\Controllers\Member\MemberController;
use Illuminate\Support\Facades\Route;

// 会員用トップページ
Route::get('/', [IndexController::class, 'index'])->name('index');

// 会員プロフィール
Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show')->withoutMiddleware('profile');
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::get('/profile/create', [ProfileController::class, 'create'])->name('profile.create')->withoutMiddleware('profile');
Route::post('/profile', [ProfileController::class, 'store'])->name('profile.store')->withoutMiddleware('profile');
Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update'])->name('profile.update');

// 会員公開情報
Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');


// 購入関連
Route::prefix('buy')->name('buy.')->group(function () {
    // 商品一覧・詳細
    Route::get('/products', [BuyProductController::class, 'index'])->name('products.index')->withoutMiddleware(['auth', 'verified', 'profile']);
    Route::get('/products/{product}', [BuyProductController::class, 'show'])->name('products.show')->withoutMiddleware(['auth', 'verified', 'profile']);
    Route::get('/products/download/{productFile}', [BuyProductController::class, 'download'])->name('products.download')->withoutMiddleware(['auth', 'verified', 'profile']);

    // ショップ
    Route::get('/shops', [BuyShopController::class, 'index'])->name('shops.index')->withoutMiddleware(['auth', 'verified', 'profile']);
    Route::get('/shops/{shop}', [BuyShopController::class, 'show'])->name('shops.show')->withoutMiddleware(['auth', 'verified', 'profile']);

    // 注文
    Route::get('/orders', [BuyOrderController::class, 'index'])->name('orders.index');    
    Route::get('/orders/{order}', [BuyOrderController::class, 'show'])->name('orders.show');
    // 決済フロー
    // Route::get('/orders/create', [BuyOrderController::class, 'create'])->name('orders.create');
    // Route::post('/orders', [BuyOrderController::class, 'store'])->name('orders.store');
    // Route::post('/orders/payment', [PaymentController::class, 'store'])->name('orders.payment.store');    
    // Route::get('/orders/{order}/payment', [PaymentController::class, 'show'])->name('orders.payment');
    // Route::post('/orders/{order}/payment', [PaymentController::class, 'store'])->name('orders.payment.store');
    // Route::get('/orders/{order}/payment/complete', [PaymentController::class, 'complete'])->name('orders.payment.complete');

    Route::get('/checkout/create', [CheckoutController::class, 'create'])->name('checkout.create'); //注文内容入力
    Route::post('/checkout/store', [CheckoutController::class, 'store'])->name('checkout.store');  // pending状態で保存,confirmにリダイレクト
    Route::get('/checkout/confirm/{order}', [CheckoutController::class, 'confirm'])->name('checkout.confirm');  // Payment Intent作成
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');  // 決済確定,completeにリダイレクト
    Route::get('/checkout/complete/{order}', [CheckoutController::class, 'complete'])->name('checkout.complete'); //完了
    
    // お気に入り
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

});

// 販売関連
Route::prefix('sell')->name('sell.')->middleware('shop')->group(function () {
    // 商品管理
    Route::get('/products', [SellProductController::class, 'index'])->name('products.index');
    Route::post('/products', [SellProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [SellProductController::class, 'edit'])->name('products.edit');
    Route::match(['put', 'patch'], '/products/{product}', [SellProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [SellProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/download/{productFile}', [SellProductController::class, 'download'])->name('products.download');
    
    // 商品ファイル管理
    Route::get('/products/{product}/files/create', [ProductFileController::class, 'create'])->name('product-files.create');
    Route::post('/products/{product}/files', [ProductFileController::class, 'store'])->name('product-files.store');
    Route::get('/products/{product}/files/{file}/edit', [ProductFileController::class, 'edit'])->name('product-files.edit');
    Route::match(['put', 'patch'], '/products/{product}/files/{file}', [ProductFileController::class, 'update'])->name('product-files.update');
    Route::delete('/products/{product}/files/{file}', [ProductFileController::class, 'destroy'])->name('product-files.destroy');
    
    // ショップ管理
    Route::get('/shop', [ShopController::class, 'show'])->name('shop.show');
    Route::get('/shop/create', [ShopController::class, 'create'])->name('shop.create')->withoutMiddleware('shop');
    Route::post('/shop', [ShopController::class, 'store'])->name('shop.store')->withoutMiddleware('shop');
    Route::get('/shop/edit', [ShopController::class, 'edit'])->name('shop.edit');
    Route::match(['put', 'patch'], '/shop', [ShopController::class, 'update'])->name('shop.update');
    
    // 売上管理
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/summary-monthly', [SaleController::class, 'summaryMonthly'])->name('sales.summary-monthly');
    Route::get('/sales/summary-product', [SaleController::class, 'summaryProduct'])->name('sales.summary-product');
    Route::get('/sales/{order}', [SaleController::class, 'show'])->name('sales.show');
});

// メッセージボックス
Route::get('/message-box', [MessageBoxController::class, 'index'])->name('message-box.index');

// メッセージ
Route::prefix('messages/{product}')->name('messages.')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('index')->withoutMiddleware(['auth', 'verified', 'profile']);
    Route::get('/create', [MessageController::class, 'create'])->name('create');
    Route::post('/confirm', [MessageController::class, 'confirm'])->name('confirm');
    Route::post('/', [MessageController::class, 'store'])->name('store');
    Route::get('/complete', [MessageController::class, 'complete'])->name('complete');
});

// レビュー
Route::prefix('reviews/{product}')->name('reviews.')->group(function () {
    Route::get('/', [ReviewController::class, 'index'])->name('index')->withoutMiddleware(['auth', 'verified', 'profile']);
    Route::get('/create', [ReviewController::class, 'create'])->name('create');
    Route::post('/confirm', [ReviewController::class, 'confirm'])->name('confirm');
    Route::post('/', [ReviewController::class, 'store'])->name('store');
    Route::get('/complete', [ReviewController::class, 'complete'])->name('complete');
});


// メッセージ返信
Route::prefix('message-replies/{message}')->name('message-replies.')->group(function () {
    Route::get('/', [MessageReplyController::class, 'index'])->name('index');
    Route::get('/create', [MessageReplyController::class, 'create'])->name('create');
    Route::post('/confirm', [MessageReplyController::class, 'confirm'])->name('confirm');
    Route::post('/', [MessageReplyController::class, 'store'])->name('store');
    Route::delete('/{reply}', [MessageReplyController::class, 'destroy'])->name('destroy');
    Route::get('/complete', [MessageReplyController::class, 'complete'])->name('complete');
    Route::patch('/public-setting', [MessageReplyController::class, 'updatePublicSetting'])->name('update-public-setting');
});

// レビュー返信
Route::prefix('review-replies/{review}')->name('review-replies.')->group(function () {
    Route::get('/', [ReviewReplyController::class, 'index'])->name('index');
    Route::get('/create', [ReviewReplyController::class, 'create'])->name('create');
    Route::post('/confirm', [ReviewReplyController::class, 'confirm'])->name('confirm');
    Route::post('/', [ReviewReplyController::class, 'store'])->name('store');
    Route::delete('/{reply}', [ReviewReplyController::class, 'destroy'])->name('destroy');
    Route::get('/complete', [ReviewReplyController::class, 'complete'])->name('complete');
});




// Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
// Route::get('/messages/{message}', [MessageController::class, 'show'])->name('messages.show');
// Route::post('/messages/{message}/replies', [MessageReplayController::class, 'store'])->name('message-replies.store');

// 通帳
Route::get('/passbook', [PassbookController::class, 'index'])->name('passbook.index');


// 出金
Route::get('/withdrawals/create', [WithdrawalController::class, 'create'])->name('withdrawals.create');
Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
Route::post('/withdrawals/confirm', [WithdrawalController::class, 'confirm'])->name('withdrawals.confirm');
Route::get('/withdrawals/complete', [WithdrawalController::class, 'complete'])->name('withdrawals.complete');




// リソースコントローラを使用する場合（使用されていないためコメントアウト）
// Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
// Route::get('/photos/create', [PhotoController::class, 'create'])->name('photos.create');
// Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
// Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
// Route::get('/photos/{photo}/edit', [PhotoController::class, 'edit'])->name('photos.edit');
// Route::match(['put', 'patch'], '/photos/{photo}', [PhotoController::class, 'update'])->name('photos.update');
// Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
// よく追加するルーティング
// Route::get('/photos/{photo}/copy', [PhotoController::class, 'copy'])->name('photos.copy');
// Route::post('/photos/confirm', [PhotoController::class, 'confirm'])->name('photos.confirm');
// Route::get('/photos/complete', [PhotoController::class, 'complete'])->name('photos.complete');


// １つにまとめる場合
// Route::resource('photos', PhotoController::class);

