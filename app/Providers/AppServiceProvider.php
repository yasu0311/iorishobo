<?php

namespace App\Providers;

use App\Services\Cart\CartService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            $expire = Config::get('auth.verification.expire', 60);
            $salutation = "よろしくお願いいたします。\n".config('shop.name');

            if (filled($notifiable->pending_email ?? null)) {
                return (new MailMessage)
                    ->subject('メールアドレス変更のご確認')
                    ->greeting($notifiable->name.' 様')
                    ->line('メールアドレス変更の手続きを受け付けました。')
                    ->line('下記のボタンより、新しいメールアドレスの確認をお願いいたします。')
                    ->action('メールアドレスを確認する', $url)
                    ->line('この確認リンクの有効期限は'.$expire.'分です。')
                    ->line('※お心当たりがない場合は、このメールを破棄してください。メールアドレスは変更されません。')
                    ->salutation($salutation);
            }

            return (new MailMessage)
                ->subject('メールアドレスのご確認')
                ->greeting($notifiable->name.' 様')
                ->line('このたびはご登録いただきありがとうございます。')
                ->line('下記のボタンより、メールアドレスの確認をお願いいたします。')
                ->action('メールアドレスを確認する', $url)
                ->line('この確認リンクの有効期限は'.$expire.'分です。')
                ->line('※このメールに心当たりがない場合は、破棄していただいて構いません。')
                ->salutation($salutation);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expire = Config::get(
                'auth.passwords.'.Config::get('auth.defaults.passwords').'.expire',
            );

            return (new MailMessage)
                ->subject('パスワード再設定のご案内')
                ->greeting($notifiable->name.' 様')
                ->line('パスワード再設定の手続きを受け付けました。')
                ->line('下記のボタンより、新しいパスワードを設定してください。')
                ->action('パスワードを再設定する', $url)
                ->line('このリンクの有効期限は'.$expire.'分です。')
                ->line('※お心当たりがない場合は、このメールを破棄してください。パスワードは変更されません。')
                ->salutation("よろしくお願いいたします。\n".config('shop.name'));
        });

        View::composer('layouts.front', function ($view) {
            $view->with(
                'cartItemCount',
                app(CartService::class)->itemQuantityTotal(),
            );
        });
    }
}
