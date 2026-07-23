<?php

namespace Tests\Unit\Rules;

use App\Http\Requests\CheckoutStoreRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutPhoneValidationTest extends TestCase
{
    #[Test]
    public function it_requires_phone_or_mobile_for_buyer(): void
    {
        $validator = Validator::make(
            CheckoutStoreRequest::normalizeInput([
                'buyer_phone' => '',
                'buyer_mobile' => '',
            ]),
            [
                'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            '電話番号または携帯番号のいずれかを入力してください。',
            $validator->errors()->first('buyer_phone'),
        );
    }

    #[Test]
    public function it_passes_when_buyer_phone_is_provided(): void
    {
        $validator = Validator::make(
            CheckoutStoreRequest::normalizeInput([
                'buyer_phone' => '0312345678',
                'buyer_mobile' => '',
            ]),
            [
                'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
            ],
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_when_buyer_mobile_is_provided(): void
    {
        $validator = Validator::make(
            CheckoutStoreRequest::normalizeInput([
                'buyer_phone' => '',
                'buyer_mobile' => '09012345678',
            ]),
            [
                'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
            ],
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_normalizes_full_width_phone_and_accepts_hyphen(): void
    {
        $input = CheckoutStoreRequest::normalizeInput([
            'buyer_phone' => '０３－１２３４－５６７８',
            'buyer_mobile' => '',
        ]);

        $this->assertSame('03-1234-5678', $input['buyer_phone']);

        $validator = Validator::make($input, [
            'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
            'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
        ]);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_rejects_phone_with_letters(): void
    {
        $validator = Validator::make(
            CheckoutStoreRequest::normalizeInput([
                'buyer_phone' => '03-abcd-5678',
                'buyer_mobile' => '',
            ]),
            [
                'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('buyer_phone'));
    }

    #[Test]
    public function it_rejects_phone_with_too_few_digits(): void
    {
        foreach (['-', '1', '090', '03-1234'] as $phone) {
            $validator = Validator::make(
                CheckoutStoreRequest::normalizeInput([
                    'buyer_phone' => $phone,
                    'buyer_mobile' => '',
                ]),
                [
                    'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                    'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
                ],
            );

            $this->assertTrue($validator->fails(), "Expected rejection for: {$phone}");
            $this->assertTrue($validator->errors()->has('buyer_phone'));
        }
    }

    #[Test]
    public function it_rejects_phone_with_too_many_digits(): void
    {
        $validator = Validator::make(
            CheckoutStoreRequest::normalizeInput([
                'buyer_phone' => '090123456789',
                'buyer_mobile' => '',
            ]),
            [
                'buyer_phone' => CheckoutStoreRequest::ruleSet()['buyer_phone'],
                'buyer_mobile' => CheckoutStoreRequest::ruleSet()['buyer_mobile'],
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('buyer_phone'));
    }
}
