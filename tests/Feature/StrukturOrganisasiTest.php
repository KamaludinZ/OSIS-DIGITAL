<?php

namespace Tests\Feature;

use App\Support\DataTiruanStruktur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrukturOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_menampilkan_bagan_periode_aktif(): void
    {
        $this->get('/struktur')->assertOk()
            ->assertSee('Bagan Kepengurusan')
            ->assertSee('/images/struktur/contoh-lebar.svg')
            ->assertSee('aspect-[4/3]', false)
            ->assertSee('Perbesar');
    }

    public function test_bagan_tinggi_dan_keadaan_kosong(): void
    {
        DataTiruanStruktur::simpan(['gambar' => '/images/struktur/contoh-tinggi.svg', 'rasio' => '3:4']);
        $this->get('/struktur')->assertOk()->assertSee('aspect-[3/4]', false)->assertSee('max-w-2xl', false);

        DataTiruanStruktur::simpan(null);
        $this->get('/struktur')->assertOk()->assertSee('Bagan struktur belum diunggah');
    }
}
