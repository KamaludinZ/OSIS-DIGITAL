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
        $this->get('/struktur')->assertOk()->assertSee('aspect-[3/4] sm:w-auto sm:h-[min(80vh,56rem)]', false)->assertSee('data-rasio="3:4"', false);

        DataTiruanStruktur::simpan(null);
        $this->get('/struktur')->assertOk()->assertSee('Bagan struktur belum diunggah');
    }

    public function test_komponen_bagan_responsif_per_rasio(): void
    {
        $lebar = $this->blade('<x-bagan-struktur gambar="/b.png" rasio="4:3" />');
        $lebar->assertSee('aspect-[4/3]', false)->assertDontSee('aspect-[3/4]', false)->assertSee('Perbesar');

        $tinggi = $this->blade('<x-bagan-struktur gambar="/b.png" rasio="3:4" :tautan="false" />');
        $tinggi->assertSee('aspect-[3/4]', false)->assertDontSee('Perbesar');
    }
}
