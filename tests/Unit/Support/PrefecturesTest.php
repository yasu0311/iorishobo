<?php

namespace Tests\Unit\Support;

use App\Support\Prefectures;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrefecturesTest extends TestCase
{
    #[Test]
    public function it_lists_all_forty_seven_prefectures(): void
    {
        $all = Prefectures::all();

        $this->assertCount(47, $all);
        $this->assertSame('北海道', $all[0]);
        $this->assertSame('沖縄県', $all[46]);
        $this->assertContains('東京都', $all);
    }
}
