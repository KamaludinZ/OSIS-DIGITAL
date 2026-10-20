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

    public function test_panel_unggah_gambar_struktur(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->get('/admin/struktur-organisasi')->assertRedirect('/login');
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]));
        $this->get('/admin/struktur-organisasi')->assertForbidden();

        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));
        $this->get('/admin/struktur-organisasi')->assertOk()->assertSee('Pratinjau halaman publik')->assertSee('contoh-lebar.svg');

        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->assertActionHasLabel('unggah', 'Ganti gambar')
            ->callAction('unggah', data: ['gambar' => \Illuminate\Http\UploadedFile::fake()->image('bagan.png', 1200, 900)])
            ->assertHasNoFormErrors()
            ->assertNotified('Bagan struktur disimpan');

        $this->assertStringContainsString('/storage/struktur/', DataTiruanStruktur::aktif()['gambar']);
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('public')->files('struktur'));
    }

    public function test_unggah_menolak_berkas_bukan_gambar_raster(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));

        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->callAction('unggah', data: ['gambar' => \Illuminate\Http\UploadedFile::fake()->create('bagan.svg', 10, 'image/svg+xml')])
            ->assertHasFormErrors(['gambar']);
    }
}
