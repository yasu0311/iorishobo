<?php

namespace Tests\Unit;

use App\Exceptions\MissingEmailTemplateException;
use App\Models\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function require_by_slug_throws_when_missing(): void
    {
        $this->expectException(MissingEmailTemplateException::class);
        $this->expectExceptionMessage('メールテンプレートが見つかりません（slug: order-confirmation）');

        EmailTemplate::requireBySlug('order-confirmation');
    }

    #[Test]
    public function require_by_slug_throws_when_body_empty(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation',
            'label' => '注文確認',
            'subject' => '件名あり',
            'body' => '',
        ]);

        $this->expectException(MissingEmailTemplateException::class);
        $this->expectExceptionMessage('件名または本文が空です');

        EmailTemplate::requireBySlug('order-confirmation');
    }

    #[Test]
    public function require_by_slug_returns_template(): void
    {
        EmailTemplate::query()->create([
            'slug' => 'order-confirmation',
            'label' => '注文確認',
            'subject' => 'ご注文ありがとうございます',
            'body' => '本文です',
        ]);

        $template = EmailTemplate::requireBySlug('order-confirmation');

        $this->assertSame('本文です', $template->body);
    }
}
