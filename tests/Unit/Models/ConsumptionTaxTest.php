<?php

namespace Tests\Unit\Models;

use App\Models\ConsumptionTax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ConsumptionTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ConsumptionTaxSeeder::class);
    }

    #[Test]
    public function it_resolves_current_standard_rate(): void
    {
        $tax = ConsumptionTax::current('2026-08-04');

        $this->assertSame('0.1000', (string) $tax->tax_rate);
        $this->assertSame(100, $tax->extractFromInclusive(1100));
        $this->assertSame('10%', $tax->percentLabel());
    }

    #[Test]
    public function it_resolves_historical_eight_percent_rate(): void
    {
        $tax = ConsumptionTax::current('2018-01-15');

        $this->assertSame('0.0800', (string) $tax->tax_rate);
        $this->assertSame(80, $tax->extractFromInclusive(1080));
        $this->assertSame('8%', $tax->percentLabel());
    }

    #[Test]
    public function it_throws_when_no_rate_covers_the_date(): void
    {
        $this->expectException(RuntimeException::class);

        ConsumptionTax::current('2010-01-01');
    }
}
