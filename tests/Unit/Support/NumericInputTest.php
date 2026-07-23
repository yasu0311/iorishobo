<?php

namespace Tests\Unit\Support;

use App\Support\NumericInput;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NumericInputTest extends TestCase
{
    #[Test]
    public function it_normalizes_full_width_postal_code(): void
    {
        $this->assertSame('1000001', NumericInput::normalizePostalCode('１０００００１'));
        $this->assertSame('1000001', NumericInput::normalizePostalCode('１００－０００１'));
        $this->assertSame('1000001', NumericInput::normalizePostalCode('100-0001'));
    }

    #[Test]
    public function it_does_not_truncate_postal_code_digits(): void
    {
        $this->assertSame('10000012', NumericInput::normalizePostalCode('100-0001-2'));
    }

    #[Test]
    public function it_does_not_strip_unexpected_postal_characters(): void
    {
        $this->assertSame('100a0001', NumericInput::normalizePostalCode('100-a-0001'));
    }

    #[Test]
    public function it_normalizes_full_width_phone(): void
    {
        $this->assertSame('03-1234-5678', NumericInput::normalizePhone('０３－１２３４－５６７８'));
        $this->assertSame('09012345678', NumericInput::normalizePhone('０９０１２３４５６７８'));
    }
}
