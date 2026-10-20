<?php

namespace Tests\Unit;

use App\Support\Angka;
use PHPUnit\Framework\TestCase;

class AngkaTest extends TestCase
{
    public function test_persen_dua_angka_di_belakang_koma(): void
    {
        $this->assertSame('57,14%', Angka::persen(4, 7));
        $this->assertSame('75,00%', Angka::persen(3, 4));
        $this->assertSame('100,00%', Angka::persen(12, 12));
        $this->assertSame('0,00%', Angka::persen(0, 0));
        $this->assertSame('33,33%', Angka::persen(1, 3));
    }
}
