<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.front', function ($view) {
            $view->with(
                'cartItemCount',
                app(CartService::class)->itemQuantityTotal(),
            );
        });
    }
}
