<?php

use App\Http\Controllers\Admin\IndexController;
use Illuminate\Support\Facades\Route;

// 管理画面（認証済みかつ管理者のみ）
Route::get('/', [IndexController::class, 'index'])->name('index');



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
