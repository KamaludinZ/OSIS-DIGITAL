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
        \App\Models\OsisPeriod::factory()->tahun(2026)->aktif()->create();
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
            ->callAction('unggah', data: ['gambar' => \Illuminate\Http\UploadedFile::fake()->image('bagan.png', 1200, 900), 'rasio' => '3:4'])
            ->assertHasNoFormErrors()
            ->assertNotified('Bagan struktur disimpan');

        $this->assertStringContainsString('/storage/struktur/', DataTiruanStruktur::aktif()['gambar']);
        $this->assertSame('3:4', DataTiruanStruktur::aktif()['rasio']);
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

    public function test_pemilih_rasio_mengubah_bingkai_tanpa_unggah_ulang(): void
    {
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));

        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->mountAction('ubahRasio')
            ->assertMountedActionModalSee(['Lebar (4:3)', 'Tinggi (3:4)'])
            ->setActionData(['rasio' => '3:4'])
            ->callMountedAction()
            ->assertNotified('Rasio tampilan diubah ke 3:4');

        $this->assertSame('3:4', DataTiruanStruktur::aktif()['rasio']);
        $this->assertSame('/images/struktur/contoh-lebar.svg', DataTiruanStruktur::aktif()['gambar']);
        $this->get('/struktur')->assertSee('data-rasio="3:4"', false);

        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->callAction('ubahRasio', data: ['rasio' => '16:9'])->assertHasFormErrors(['rasio']);
    }

    public function test_ganti_dan_hapus_gambar_dengan_konfirmasi(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));

        $halaman = \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->mountAction('unggah')
            ->assertMountedActionModalSee(['Ganti bagan struktur', 'Gambar saat ini', 'akan diganti'])
            ->callMountedAction()
            ->assertHasFormErrors(['gambar' => 'required']);

        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->mountAction('hapus')
            ->assertMountedActionModalSee(['Hapus bagan struktur?', 'Bagan struktur belum diunggah', 'Ya, hapus'])
            ->callMountedAction()
            ->assertNotified('Bagan struktur dihapus')
            ->assertActionHidden('hapus')
            ->assertActionHidden('ubahRasio')
            ->assertActionHasLabel('unggah', 'Unggah gambar');

        $this->assertNull(DataTiruanStruktur::aktif());
        $this->get('/admin/struktur-organisasi')->assertSee('Belum ada bagan untuk periode ini');
    }

    public function test_keterangan_belum_tersedia_dan_pesan_galat(): void
    {
        // Tanpa periode aktif dan tanpa gambar.
        DataTiruanStruktur::simpan(null);
        $this->get('/struktur')->assertOk()->assertSee('Kepengurusan baru sedang disiapkan');

        \App\Models\OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $this->get('/struktur')->assertOk()->assertSee('Bagan struktur belum diunggah');

        // Gambar ada: pesan cadangan bila gagal dimuat sudah disiapkan.
        DataTiruanStruktur::simpan(['gambar' => '/images/struktur/contoh-lebar.svg', 'rasio' => '4:3']);
        $this->get('/struktur')->assertOk()->assertSee('Gambar bagan gagal dimuat')->assertSee('x-on:error="gagal = true"', false);

        // Pesan galat unggah dalam bahasa Indonesia.
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));
        \Livewire\Livewire::test(\App\Filament\Pages\StrukturOrganisasi::class)
            ->callAction('unggah', data: ['gambar' => \Illuminate\Http\UploadedFile::fake()->image('besar.png')->size(6000), 'rasio' => '4:3'])
            ->assertHasFormErrors(['gambar'])
            ->tap(fn ($uji) => $this->assertStringContainsString('Ukuran gambar maksimal 5 MB',
                collect($uji->errors()->getMessages())->flatten()->implode(' ')));
    }
}
