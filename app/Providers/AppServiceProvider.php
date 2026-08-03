<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
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
        if ($root = config('app.url')) {
            URL::forceRootUrl($root);
        }

        // 署名は相対パス基準（ホスト差で壊れない）。メール本文用に APP_URL を付与する。
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $relative = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                absolute: false,
            );

            return URL::to($relative);
        });

        View::composer('layouts.front', function ($view) {
            $view->with(
                'cartItemCount',
                app(CartService::class)->itemQuantityTotal(),
            );
        });
    }
}
