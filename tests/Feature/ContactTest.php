<?php

namespace Tests\Feature;

use App\Mail\ContactAdminMail;
use App\Mail\ContactReceivedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function contact_form_can_be_submitted(): void
    {
        Mail::fake();
        $this->seed(\Database\Seeders\EmailTemplateSeeder::class);

        config([
            'shop.email' => 'shop@example.com',
        ]);

        $payload = [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'inquiry_type' => '商品について',
            'message' => '在庫の確認をお願いします。',
        ];

        $this->post(route('contacts.confirm'), $payload)
            ->assertOk()
            ->assertSee('山田太郎');

        $this->post(route('contacts.store'))
            ->assertRedirect(route('contacts.complete'));

        Mail::assertSent(ContactAdminMail::class, function (ContactAdminMail $mail) {
            return $mail->hasTo('shop@example.com')
                && $mail->contact['name'] === '山田太郎';
        });

        Mail::assertSent(ContactReceivedMail::class, function (ContactReceivedMail $mail) {
            return $mail->hasTo('taro@example.com');
        });
    }

    #[Test]
    public function contact_completes_even_if_customer_reply_template_is_missing(): void
    {
        Mail::fake();
        $this->seed(\Database\Seeders\EmailTemplateSeeder::class);
        \App\Models\EmailTemplate::query()->where('slug', 'contact-received')->delete();

        config(['shop.email' => 'shop@example.com']);

        $payload = [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'inquiry_type' => '商品について',
            'message' => '在庫の確認をお願いします。',
        ];

        $this->post(route('contacts.confirm'), $payload);
        $this->post(route('contacts.store'))
            ->assertRedirect(route('contacts.complete'));

        Mail::assertSent(ContactAdminMail::class);
        Mail::assertNotSent(ContactReceivedMail::class);
    }

    #[Test]
    public function contact_fails_when_admin_mail_cannot_be_sent(): void
    {
        config(['shop.email' => 'shop@example.com']);

        $payload = [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'inquiry_type' => '商品について',
            'message' => '在庫の確認をお願いします。',
        ];

        $this->post(route('contacts.confirm'), $payload);

        Mail::shouldReceive('to')
            ->once()
            ->with('shop@example.com')
            ->andThrow(new \RuntimeException('smtp unavailable'));

        $this->post(route('contacts.store'))
            ->assertRedirect(route('contacts.create'))
            ->assertSessionHasErrors('message');
    }

    #[Test]
    public function contact_complete_requires_prior_submission(): void
    {
        $this->get(route('contacts.complete'))
            ->assertRedirect(route('contacts.create'));
    }

    #[Test]
    public function contact_confirm_validates_input(): void
    {
        $this->post(route('contacts.confirm'), [])
            ->assertSessionHasErrors(['name', 'email', 'inquiry_type', 'message']);
    }
}
