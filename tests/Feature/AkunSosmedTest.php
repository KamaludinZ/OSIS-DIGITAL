<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\PlatformSosmed;
use App\Models\OsisPeriod;
use App\Models\SocialLink;
use App\Models\User;
use App\Services\LayananAkunSosmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AkunSosmedTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $lama;

    private OsisPeriod $aktif;

    private SocialLink $arsip;

    private SocialLink $instagram;

    private SocialLink $youtube;

    private SocialLink $tiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $this->arsip = SocialLink::create(['platform' => 'instagram', 'nama_akun' => '@osim2526.mtsn2', 'tautan' => 'https://www.instagram.com/osim2526.mtsn2']);

        app(\App\Services\LayananPeriode::class)->aktifkan($this->aktif = OsisPeriod::factory()->tahun(2026)->create());
        $this->instagram = SocialLink::create(['platform' => 'instagram', 'nama_akun' => '@osim.mtsn2malang', 'tautan' => 'https://www.instagram.com/osim.mtsn2malang', 'urutan' => 1]);
        $this->youtube = SocialLink::create(['platform' => 'youtube', 'nama_akun' => 'OSIM MTsN 2 Kota Malang', 'tautan' => 'https://www.youtube.com/@osimmtsn2malang', 'urutan' => 2]);
        $this->tiktok = SocialLink::create(['platform' => 'tiktok', 'nama_akun' => '@osim.mtsn2', 'tautan' => 'https://www.tiktok.com/@osim.mtsn2', 'urutan' => 3]);
    }

    private function masukPengurus(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
    }

    public function test_halaman_kelola_menampilkan_akun_periode_aktif_dan_arsip(): void
    {
        $this->masukPengurus();

        $this->get('/kelola/sosmed')->assertOk()
            ->assertSeeInOrder(['Akun resmi OSIM', '2026/2027', '3', '2025/2026', '1', '@osim.mtsn2malang', 'Konten baru']);

        Volt::test('kelola.akun-sosmed')
            ->assertSee('OSIM MTsN 2 Kota Malang')
            ->assertDontSee('@osim2526.mtsn2')
            ->set('periode', $this->lama->id)
            ->assertSee('@osim2526.mtsn2')
            ->assertSee('hanya bisa dilihat');
    }

    public function test_hanya_pengelola_publikasi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        Volt::test('kelola.akun-sosmed')->assertForbidden();
    }

    public function test_periode_tidak_dikenal_kembali_ke_periode_aktif(): void
    {
        $this->masukPengurus();

        Volt::test('kelola.akun-sosmed')
            ->set('periode', 9999)
            ->assertSet('periode', $this->aktif->id)
            ->assertDontSee('hanya bisa dilihat')
            ->set('periode', $this->lama->id)
            ->assertSee('grayscale', false);
    }

    public function test_tambah_ubah_hapus_akun_dengan_validasi_ramah(): void
    {
        $this->masukPengurus();

        Volt::test('kelola.akun-sosmed')
            // Satu akun per platform per periode.
            ->set('platform', 'instagram')->set('namaAkun', '@baru')->set('tautan', 'https://www.instagram.com/baru')
            ->call('simpan')->assertHasErrors(['platform'])->assertSee('Akun platform ini untuk periode aktif sudah ada')
            // Domain harus sesuai platform; http diterima lalu disimpan https.
            ->call('ubah', $this->youtube->id)->assertSet('platform', 'youtube')
            ->set('tautan', 'https://www.tiktok.com/@x')->call('simpan')->assertHasErrors(['tautan'])
            ->assertSee('Tautan ini bukan alamat YouTube.')
            ->set('tautan', 'http://www.youtube.com/@osimbaru')->call('simpan')->assertHasNoErrors()
            ->assertSee('Akun diperbarui.')->assertSet('diubah', null)
            ->call('hapus', $this->tiktok->id)->assertSee('Akun dihapus.')
            ->set('platform', 'tiktok')->set('namaAkun', '@osim.baru')->set('tautan', 'www.tiktok.com/@osim.baru')
            ->call('simpan')->assertHasNoErrors()->assertSee('Akun ditambahkan.');

        $this->assertSame('https://www.youtube.com/@osimbaru', $this->youtube->fresh()->tautan);
        $this->assertModelMissing($this->tiktok);
        $baru = SocialLink::where('platform', 'tiktok')->sole();
        $this->assertSame('https://www.tiktok.com/@osim.baru', $baru->tautan);
        $this->assertSame($this->aktif->id, $baru->periode_id);
        $this->assertSame(3, SocialLink::count());
    }

    public function test_akun_arsip_tidak_bisa_diubah(): void
    {
        $this->masukPengurus();

        Volt::test('kelola.akun-sosmed')
            ->set('periode', $this->lama->id)
            ->assertDontSee('Tambah akun')
            ->call('hapus', $this->arsip->id)->assertForbidden();

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        app(LayananAkunSosmed::class)->hapus(SocialLink::diPeriode($this->lama)->first());
    }

    public function test_layanan_crud_menolak_tanpa_periode_aktif(): void
    {
        $this->aktif->update(['status' => 'arsip']);

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        app(LayananAkunSosmed::class)->tambah(['platform' => 'youtube', 'nama_akun' => '@osim', 'tautan' => 'https://youtube.com/@osim']);
    }

    public function test_tautan_periode_aktif_tampil_di_halaman_publik(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Akun media sosial resmi OSIM')
            ->assertSeeInOrder(['@osim.mtsn2malang', 'OSIM MTsN 2 Kota Malang', '@osim.mtsn2'])
            ->assertSee('href="'.route('sosmed.akun', $this->instagram->id).'"', false)
            ->assertDontSee('href="https://www.instagram.com/osim.mtsn2malang"', false)
            ->assertDontSee('@osim2526.mtsn2');

        $this->get('/saran')->assertOk()->assertSee('@osim.mtsn2malang');
        $this->assertSame([PlatformSosmed::Instagram->value, 'youtube', 'tiktok'], array_column(app(LayananAkunSosmed::class)->publik(), 'platform'));
    }

    public function test_halaman_pengalihan_aman(): void
    {
        $this->get('/sosmed/akun/'.$this->instagram->id)->assertOk()
            ->assertSee('Membuka Instagram')
            ->assertSee('www.instagram.com')
            ->assertSee('href="'.route('sosmed.akun.buka', $this->instagram->id).'" rel="noopener noreferrer"', false)
            ->assertSee('Tetap di situs OSIM');

        // Akun arsip / tidak dikenal tidak bisa dipakai untuk dialihkan.
        $this->get('/sosmed/akun/'.$this->arsip->id)->assertNotFound();
        $this->get('/sosmed/akun/99999')->assertNotFound();

        // Tautan yang tidak cocok domainnya ditolak walau tersimpan (mis. diubah langsung di basis data).
        \Illuminate\Support\Facades\DB::table('social_links')->where('id', $this->tiktok->id)->update(['tautan' => 'https://tiktok.com.jahat.test/x']);
        $this->get('/sosmed/akun/'.$this->tiktok->id)->assertNotFound();
        app()->forgetScopedInstances(); // permintaan baru (memo publik() hanya per permintaan)
        $this->assertNotContains('tiktok', array_column(app(LayananAkunSosmed::class)->publik(), 'platform'));
    }

    public function test_hapus_dijaga_hanya_periode_aktif(): void
    {
        $layanan = app(LayananAkunSosmed::class);

        // Akun periode yang baru disiapkan (belum aktif) tidak bisa dihapus/diubah dari sini.
        $depan = OsisPeriod::factory()->tahun(2027)->create();
        $akunDepan = SocialLink::factory()->create(['periode_id' => $depan->id]);
        foreach ([fn () => $layanan->hapus($akunDepan), fn () => $layanan->ubah($akunDepan, ['platform' => 'instagram', 'nama_akun' => '@x', 'tautan' => 'https://instagram.com/x'])] as $aksi) {
            try {
                $aksi();
                $this->fail('Seharusnya ditolak.');
            } catch (\App\Exceptions\PeriodeDitolak $e) {
                $this->assertStringContainsString('milik periode 2027/2028', $e->getMessage());
            }
        }
        $this->assertModelExists($akunDepan);

        // Akun periode aktif boleh dihapus.
        $layanan->hapus($this->youtube);
        $this->assertModelMissing($this->youtube);
    }

    public function test_query_publik_sekali_per_permintaan_dan_segar_setelah_diubah(): void
    {
        $kueri = 0;
        \Illuminate\Support\Facades\DB::listen(function ($q) use (&$kueri) {
            $kueri += str_contains($q->sql, '"social_links"') ? 1 : 0;
        });

        $this->get('/')->assertOk();
        $this->assertSame(1, $kueri, 'Beranda + kaki halaman cukup satu kueri akun.');

        $layanan = app(LayananAkunSosmed::class);
        $this->assertCount(3, $layanan->publik());
        $layanan->hapus($this->tiktok);
        $this->assertCount(2, $layanan->publik());
        $this->assertSame(['id', 'platform', 'label', 'nama_akun', 'url'], array_keys($layanan->publik()[0]));
    }

    public function test_endpoint_pengalihan_aman_menghitung_dan_menolak_alamat_tidak_valid(): void
    {
        $this->get('/sosmed/akun/'.$this->instagram->id.'/buka')->assertRedirect('https://www.instagram.com/osim.mtsn2malang');
        $this->get('/sosmed/akun/'.$this->instagram->id.'/buka')->assertRedirect('https://www.instagram.com/osim.mtsn2malang');
        $this->assertSame(1, $this->instagram->fresh()->jumlah_klik, 'Dihitung sekali per sesi.');

        $this->get('/sosmed/akun/'.$this->arsip->id.'/buka')->assertNotFound();
        $this->get('/sosmed/akun/abc/buka')->assertNotFound();

        \Illuminate\Support\Facades\DB::table('social_links')->where('id', $this->tiktok->id)->update(['tautan' => 'javascript:alert(1)']);
        $this->get('/sosmed/akun/'.$this->tiktok->id.'/buka')->assertNotFound();
        $this->assertSame(0, $this->tiktok->fresh()->jumlah_klik);
    }
}
