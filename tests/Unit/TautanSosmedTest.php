<?php

namespace Tests\Unit;

use App\Enums\PlatformSosmed;
use App\Rules\TautanSosmed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TautanSosmedTest extends TestCase
{
    private function galat(PlatformSosmed $platform, string $tautan): ?string
    {
        $pesan = null;
        (new TautanSosmed($platform))->validate('tautan', $tautan, function ($p) use (&$pesan) {
            $pesan = $p;
        });

        return $pesan;
    }

    public static function tautanSah(): array
    {
        return [
            'https' => [PlatformSosmed::Instagram, 'https://www.instagram.com/osim.mtsn2malang'],
            'http diterima' => [PlatformSosmed::Instagram, 'http://instagram.com/osim'],
            'tanpa skema' => [PlatformSosmed::TikTok, 'www.tiktok.com/@osim'],
            'youtu.be' => [PlatformSosmed::YouTube, 'https://youtu.be/abc'],
        ];
    }

    #[DataProvider('tautanSah')]
    public function test_tautan_sah(PlatformSosmed $platform, string $tautan): void
    {
        $this->assertNull($this->galat($platform, $tautan));
        $this->assertStringStartsWith('https://', TautanSosmed::normalisasi($tautan));
    }

    public function test_tautan_tidak_sah(): void
    {
        $this->assertStringContainsString('bukan alamat Instagram', $this->galat(PlatformSosmed::Instagram, 'https://www.tiktok.com/@osim'));
        $this->assertStringContainsString('bukan alamat Instagram', $this->galat(PlatformSosmed::Instagram, 'https://instagram.com.jahat.test/x'));
        $this->assertStringContainsString('http:// atau https://', $this->galat(PlatformSosmed::Instagram, 'javascript:alert(1)'));
        $this->assertStringContainsString('http:// atau https://', $this->galat(PlatformSosmed::Instagram, 'ftp://instagram.com/x'));
    }

    public function test_normalisasi(): void
    {
        $this->assertSame('https://instagram.com/osim', TautanSosmed::normalisasi('  http://instagram.com/osim '));
        $this->assertSame('https://www.tiktok.com/@osim', TautanSosmed::normalisasi('www.tiktok.com/@osim'));
    }
}
