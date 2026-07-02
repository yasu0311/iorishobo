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
