<?php

namespace App\Exceptions;

use RuntimeException;

class MissingEmailTemplateException extends RuntimeException
{
    public static function forSlug(string $slug): self
    {
        return new self(
            "メールテンプレートが見つかりません（slug: {$slug}）。"
            .'管理画面のメールテンプレートを登録するか、'
            .'php artisan db:seed --class=EmailTemplateSeeder を実行してください。'
        );
    }

    public static function incomplete(string $slug): self
    {
        return new self(
            "メールテンプレート「{$slug}」の件名または本文が空です。"
            .'管理画面で設定してください。'
        );
    }
}
