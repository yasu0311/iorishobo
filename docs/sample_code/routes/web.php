<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\StaticController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SitemapController;

// トップページ
Route::get('/', [IndexController::class, 'index'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap.xml');

// お問い合わせ（送信・確認はレートリミット 5回/分）
Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
Route::post('/contacts/confirm', [ContactController::class, 'confirm'])->name('contacts.confirm')->middleware('throttle:5,1');
Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store')->middleware('throttle:5,1');
Route::post('/contacts/back', [ContactController::class, 'back'])->name('contacts.back');
Route::get('/contacts/complete', [ContactController::class, 'complete'])->name('contacts.complete');

// 静的ページ
Route::prefix('static')->name('static.')->group(function () {
    Route::get('/copyright-purchaser', [StaticController::class, 'copyrightPurchaser'])->name('copyright-purchaser');
    Route::get('/copyright-shop', [StaticController::class, 'copyrightShop'])->name('copyright-shop');
    Route::get('/faq', [StaticController::class, 'faq'])->name('faq');
    Route::get('/fee', [StaticController::class, 'fee'])->name('fee');
    Route::get('/how-to-buy', [StaticController::class, 'howToBuy'])->name('how-to-buy');
    Route::get('/how-to-sell', [StaticController::class, 'howToSell'])->name('how-to-sell');
    Route::get('/law', [StaticController::class, 'law'])->name('law');
    Route::get('/privacy-policy', [StaticController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('/terms', [StaticController::class, 'terms'])->name('terms');    
});

// 認証が必要なプロフィール関連
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



// リソースコントローラを使用する場合
// Route::get('/photos', [PhotoController::class, 'index'])->name('photos.index');
// Route::get('/photos/create', [PhotoController::class, 'create'])->name('photos.create');
// Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
// Route::get('/photos/{photo}', [PhotoController::class, 'show'])->name('photos.show');
// Route::get('/photos/{photo}/edit', [PhotoController::class, 'edit'])->name('photos.edit');
// Route::match(['put', 'patch'], '/photos/{photo}', [PhotoController::class, 'update'])->name('photos.update');
// Route::delete('/photos/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
// １つにまとめる場合
// Route::resource('photos', PhotoController::class);


require __DIR__.'/auth.php';
