<?php

namespace Tests\Feature;

use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananModePemilihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Matriks mode pemilihan × alamat publik: mana yang terbuka, terkunci, dan menu yang tampil. */
class PemblokiranMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OsisPeriod::factory()->tahun(2026)->aktif()->create(['mode_pemilihan' => true]);
        $this->seed([\Database\Seeders\PemilihanSeeder::class]);
    }

    private function setelMode(bool $aktif): void
    {
        app(LayananModePemilihan::class)->ubah($aktif, User::factory()->create());
    }

    public static function alamatPemilihan(): array
    {
        return [
            'bilik suara' => ['/bilik-suara', 'bilik-suara'],
            'bukti memilih' => ['/bilik-suara/bukti', 'bilik-suara'],
            'daftar kandidat' => ['/kandidat', 'kandidat'],
            'hasil' => ['/hasil', 'hasil'],
            'laporan hasil' => ['/hasil/laporan', 'hasil'],
        ];
    }

    /** Tidak dikunci sakelar (halaman boleh punya aturan sendiri, mis. bukti alih ke bilik suara). */
    private function tidakTerkunci(string $alamat): void
    {
        $respons = $this->get($alamat);

        $this->assertNotSame(423, $respons->getStatusCode(), $alamat);
        $this->assertStringNotContainsString('/terkunci', (string) $respons->headers->get('Location'), $alamat);
    }

    #[DataProvider('alamatPemilihan')]
    public function test_alamat_pemilihan_terkunci_hanya_saat_mode_nonaktif(string $alamat, string $halaman): void
    {
        $this->tidakTerkunci($alamat);

        $this->setelMode(false);
        $this->get($alamat)->assertRedirect(route('terkunci', ['halaman' => $halaman]));
        $this->getJson($alamat)->assertStatus(423);

        $this->setelMode(true);
        $this->tidakTerkunci($alamat);
    }

    public function test_profil_kandidat_terkunci_saat_mode_nonaktif(): void
    {
        $calon = \App\Models\Candidate::query()->first();
        $this->get('/kandidat/'.$calon->id)->assertOk();

        $this->setelMode(false);
        $this->get('/kandidat/'.$calon->id)->assertRedirect(route('terkunci', ['halaman' => 'kandidat']));
    }

    public function test_struktur_dan_saran_selalu_terbuka(): void
    {
        foreach ([true, false] as $mode) {
            $this->setelMode($mode);
            $this->get('/struktur')->assertOk();
            $this->get('/saran')->assertOk();
            $this->get('/')->assertOk();
        }
    }

    public function test_menu_berganti_bolak_balik_mengikuti_mode(): void
    {
        $menuPemilihan = ['Bilik Suara', 'Kandidat', 'Hasil'];
        $menuOrganisasi = ['Struktur Organisasi', 'Kotak Saran'];

        foreach ([false, true, false] as $mode) {
            $this->setelMode($mode);
            $halaman = $this->get('/')->assertOk()->assertSeeInOrder(['Menu utama', 'Beranda', ...($mode ? $menuPemilihan : $menuOrganisasi)]);

            foreach ($mode ? $menuOrganisasi : ['Bilik Suara'] as $tidakAda) {
                $halaman->assertDontSee('<span class="max-w-full truncate">'.($tidakAda === 'Struktur Organisasi' ? 'Struktur' : ($tidakAda === 'Kotak Saran' ? 'Saran' : $tidakAda)).'</span>', false);
            }
        }
    }

    public function test_tanpa_periode_aktif_situs_tetap_mode_pemilihan(): void
    {
        OsisPeriod::aktif()->update(['status' => 'arsip']);

        $this->get('/bilik-suara')->assertOk();
        $this->get('/')->assertSeeInOrder(['Beranda', 'Bilik Suara', 'Kandidat', 'Hasil']);
    }
}
