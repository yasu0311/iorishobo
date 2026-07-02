<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;


class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
            // 通常のWebルート
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // 管理者用
            Route::middleware(['web', 'auth', 'can:admin','verified'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // 一般会員用（ログイン済みの会員のみ）
            Route::middleware(['web', 'auth', 'verified', 'profile'])
                ->prefix('member')
                ->name('member.')
                ->group(base_path('routes/member.php'));

            // APIルート（デフォルト）
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
    }
}
