<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\StrukturOrganisasi;
use App\Models\OrganizationStructure;
use App\Models\OsisPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StrukturOrganisasiTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $periode;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->periode = OsisPeriod::factory()->tahun(2026)->aktif()->create();
    }

    private function bagan(string $rasio = '4:3'): OrganizationStructure
    {
        Storage::disk('public')->put('struktur/bagan.png', 'x');

        return OrganizationStructure::create(['gambar' => 'struktur/bagan.png', 'rasio' => $rasio]);
    }

    private function masukPengurus(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
    }

    public function test_halaman_publik_menampilkan_bagan_periode_aktif(): void
    {
        $this->bagan();

        $this->get('/struktur')->assertOk()
            ->assertSee('Bagan Kepengurusan')
            ->assertSee('/storage/struktur/bagan.png')
            ->assertSee('aspect-[4/3]', false)
            ->assertSee('Perbesar');
    }

    public function test_bagan_tinggi_dan_keadaan_kosong(): void
    {
        $this->get('/struktur')->assertOk()->assertSee('Bagan struktur belum diunggah');

        $this->bagan('3:4');
        $this->get('/struktur')->assertOk()->assertSee('aspect-[3/4] sm:w-auto sm:h-[min(80vh,56rem)]', false)->assertSee('data-rasio="3:4"', false);
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
        $this->get('/admin/struktur-organisasi')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/struktur-organisasi')->assertForbidden();

        $this->masukPengurus();
        $this->get('/admin/struktur-organisasi')->assertOk()->assertSee('Belum ada bagan untuk periode ini');

        Livewire::test(StrukturOrganisasi::class)
            ->assertActionHasLabel('unggah', 'Unggah gambar')
            ->callAction('unggah', data: ['gambar' => UploadedFile::fake()->image('bagan.png', 1200, 900), 'rasio' => '3:4'])
            ->assertHasNoFormErrors()
            ->assertNotified('Bagan struktur disimpan');

        $bagan = OrganizationStructure::aktif();
        $this->assertSame('3:4', $bagan->rasio);
        $this->assertSame($this->periode->id, $bagan->periode_id);
        $this->assertTrue($bagan->pengunggah->is(auth()->user()));
        Storage::disk('public')->assertExists($bagan->gambar);
        $this->get('/admin/struktur-organisasi')->assertSee('Pratinjau halaman publik')->assertSee($bagan->urlGambar());
    }

    public function test_unggah_menolak_berkas_bukan_gambar_raster(): void
    {
        $this->masukPengurus();

        Livewire::test(StrukturOrganisasi::class)
            ->callAction('unggah', data: ['gambar' => UploadedFile::fake()->create('bagan.svg', 10, 'image/svg+xml'), 'rasio' => '4:3'])
            ->assertHasFormErrors(['gambar']);
    }

    public function test_pemilih_rasio_mengubah_bingkai_tanpa_unggah_ulang(): void
    {
        $bagan = $this->bagan();
        $this->masukPengurus();

        Livewire::test(StrukturOrganisasi::class)
            ->mountAction('ubahRasio')
            ->assertMountedActionModalSee(['Lebar (4:3)', 'Tinggi (3:4)'])
            ->setActionData(['rasio' => '3:4'])
            ->callMountedAction()
            ->assertNotified('Rasio tampilan diubah ke 3:4');

        $this->assertSame('3:4', $bagan->fresh()->rasio);
        $this->assertSame('struktur/bagan.png', $bagan->fresh()->gambar);
        $this->get('/struktur')->assertSee('data-rasio="3:4"', false);

        Livewire::test(StrukturOrganisasi::class)
            ->callAction('ubahRasio', data: ['rasio' => '16:9'])->assertHasFormErrors(['rasio']);
    }

    public function test_ganti_dan_hapus_gambar_dengan_konfirmasi(): void
    {
        $this->bagan();
        $this->masukPengurus();

        Livewire::test(StrukturOrganisasi::class)
            ->mountAction('unggah')
            ->assertMountedActionModalSee(['Ganti bagan struktur', 'Gambar saat ini', 'akan diganti'])
            ->callMountedAction()
            ->assertHasFormErrors(['gambar' => 'required']);

        Livewire::test(StrukturOrganisasi::class)
            ->mountAction('hapus')
            ->assertMountedActionModalSee(['Hapus bagan struktur?', 'Bagan struktur belum diunggah', 'Ya, hapus'])
            ->callMountedAction()
            ->assertNotified('Bagan struktur dihapus')
            ->assertActionHidden('hapus')
            ->assertActionHidden('ubahRasio')
            ->assertActionHasLabel('unggah', 'Unggah gambar');

        $this->assertNull(OrganizationStructure::aktif());
        Storage::disk('public')->assertMissing('struktur/bagan.png');
    }

    public function test_keterangan_belum_tersedia_dan_pesan_galat(): void
    {
        // Tanpa periode aktif.
        $this->periode->update(['status' => 'arsip']);
        $this->get('/struktur')->assertOk()->assertSee('Kepengurusan baru sedang disiapkan');
        $this->masukPengurus();
        Livewire::test(StrukturOrganisasi::class)->assertActionDisabled('unggah');

        app(\App\Services\LayananPeriode::class)->aktifkan(OsisPeriod::factory()->tahun(2027)->create());
        $this->get('/struktur')->assertOk()->assertSee('Bagan struktur belum diunggah');

        // Gambar ada: pesan cadangan bila gagal dimuat sudah disiapkan.
        $this->bagan();
        $this->get('/struktur')->assertOk()->assertSee('Gambar bagan gagal dimuat')->assertSee('x-on:error="gagal = true"', false);

        // Pesan galat unggah dalam bahasa Indonesia.
        Livewire::test(StrukturOrganisasi::class)
            ->callAction('unggah', data: ['gambar' => UploadedFile::fake()->image('besar.png')->size(6000), 'rasio' => '4:3'])
            ->assertHasFormErrors(['gambar'])
            ->tap(fn ($uji) => $this->assertStringContainsString('Ukuran gambar maksimal 5 MB',
                collect($uji->errors()->getMessages())->flatten()->implode(' ')));
    }
}
