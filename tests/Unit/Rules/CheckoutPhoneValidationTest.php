<?php

namespace Tests\Unit\Rules;

use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutPhoneValidationTest extends TestCase
{
    #[Test]
    public function it_requires_phone_or_mobile_for_buyer(): void
    {
        $validator = Validator::make(
            ['buyer_phone' => '', 'buyer_mobile' => ''],
            [
                'buyer_phone' => 'nullable|required_without:buyer_mobile',
                'buyer_mobile' => 'nullable|required_without:buyer_phone',
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
            ['buyer_phone' => '0312345678', 'buyer_mobile' => ''],
            [
                'buyer_phone' => 'nullable|required_without:buyer_mobile',
                'buyer_mobile' => 'nullable|required_without:buyer_phone',
            ],
        );

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_when_buyer_mobile_is_provided(): void
    {
        $validator = Validator::make(
            ['buyer_phone' => '', 'buyer_mobile' => '09012345678'],
            [
                'buyer_phone' => 'nullable|required_without:buyer_mobile',
                'buyer_mobile' => 'nullable|required_without:buyer_phone',
            ],
        );

        $this->assertFalse($validator->fails());
    }
}
