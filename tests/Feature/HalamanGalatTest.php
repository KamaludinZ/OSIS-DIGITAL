<?php

namespace Tests\Feature;

use Tests\TestCase;

class HalamanGalatTest extends TestCase
{
    public function test_halaman_404_bergaya_osim(): void
    {
        $this->get('/tidak-ada-halaman-ini')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke Beranda')
            ->assertSee('/images/brand/logo-mtsn2.png');
    }

    public function test_semua_halaman_galat_dan_perawatan_bisa_dirender(): void
    {
        foreach (['401' => 'Khusus pengurus &amp; panitia', '403' => 'Akses tidak diizinkan', '419' => 'Sesi sudah kedaluwarsa', '429' => 'Terlalu banyak permintaan', '500' => 'Terjadi kesalahan di server', '503' => 'Sedang dalam perawatan'] as $kode => $judul) {
            $html = view("errors.$kode")->render();
            $this->assertStringContainsString($judul, $html, "errors.$kode");
            $this->assertStringContainsString('Kode '.$kode, $html);
        }
    }
}
