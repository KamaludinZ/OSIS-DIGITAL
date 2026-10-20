<?php

namespace Tests\Feature;

use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananModePemilihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OsisPeriod::factory()->tahun(2026)->aktif()->create(['mode_pemilihan' => true]);
    }

    public function test_menu_saat_mode_pemilihan_aktif(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Menu utama', 'Beranda', 'Bilik Suara', 'Kandidat', 'Hasil'])
            ->assertDontSee('Kotak Saran')
            ->assertSee('grid-cols-4', false);
    }

    public function test_menu_berganti_saat_mode_pemilihan_nonaktif(): void
    {
        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Menu utama', 'Beranda', 'Struktur Organisasi', 'Kotak Saran'])
            // Tab bar HP memakai label pendek dan tiga kolom.
            ->assertSeeInOrder(['Struktur', 'Saran'])
            ->assertSee('grid-cols-3', false)
            ->assertDontSee('>Bilik Suara<', false);
    }

    public function test_penanda_bilik_suara_dibuka_saat_mode_aktif(): void
    {
        $this->get('/')->assertDontSee('(sedang dibuka)');

        \App\Models\Election::factory()->create(['status' => 'berjalan', 'mulai' => now()->subHour(), 'selesai' => now()->addHours(5)]);

        $this->get('/')->assertOk()->assertSeeInOrder(['Bilik Suara', '(sedang dibuka)']);

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());
        $this->get('/')->assertDontSee('(sedang dibuka)');
    }

    public function test_tampilan_mode_nonaktif_mengarah_ke_struktur_dan_saran(): void
    {
        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());

        $this->get('/')->assertOk()
            ->assertSee('href="'.route('struktur').'"', false)
            ->assertSee('href="'.route('saran').'"', false)
            ->assertSee('Kenali pengurus & sampaikan aspirasimu', false)
            ->assertDontSee('Kartu Pemilihan');

        $this->get('/struktur')->assertOk()->assertSee('Struktur Organisasi')->assertSee('2026/2027');
        $this->get('/saran')->assertOk()->assertSee('Kotak Saran');
    }

    public function test_halaman_pemberitahuan_terkunci_ramah(): void
    {
        // Saat mode aktif, halaman terkunci langsung meneruskan ke tujuan.
        $this->get('/terkunci?halaman=kandidat')->assertRedirect(route('kandidat.index'));
        $this->get('/terkunci?halaman=ngawur')->assertRedirect(route('home'));

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());

        $this->get('/terkunci?halaman=bilik-suara')->assertOk()
            ->assertSee('Bilik Suara sedang ditutup')
            ->assertSee('Di luar masa pemilihan')
            ->assertSee('href="'.route('struktur').'"', false)
            ->assertSee('href="'.route('saran').'"', false);

        $this->get('/terkunci?halaman=hasil')->assertOk()->assertSee('Hasil Pemilihan sedang ditutup');
        $this->get('/terkunci')->assertOk()->assertSee('Halaman pemilihan sedang ditutup');
        $this->get('/terkunci?halaman=<b>x</b>')->assertOk()->assertSee('Halaman pemilihan sedang ditutup')->assertDontSee('<b>x</b>', false);
    }

    public function test_halaman_publik_memantau_pergantian_mode(): void
    {
        $this->get('/')->assertOk()->assertSee("\$store.mode.pantau(true, '".route('mode-publik')."')", false);
        $this->getJson('/mode-publik')->assertOk()->assertExactJson(['pemilihan' => true])
            ->assertHeader('Cache-Control', 'max-age=30, public');

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());

        $this->getJson('/mode-publik')->assertExactJson(['pemilihan' => false]);
        $this->get('/')->assertSee("\$store.mode.pantau(false, ", false);
    }

    /** Jumlah tautan per label di satu bilah menu. */
    private function hitungMenu(string $html, string $kelas): array
    {
        preg_match('/<nav aria-label="Menu utama" class="'.$kelas.'.*?<\/nav>/s', $html, $nav);
        preg_match_all('/<a href="([^"]+)"/', $nav[0], $tautan);

        return array_count_values($tautan[1]);
    }

    public function test_tiap_menu_muncul_sekali_di_desktop_dan_ponsel(): void
    {
        foreach ([true, false] as $mode) {
            app(LayananModePemilihan::class)->ubah($mode, User::factory()->create());
            $html = $this->get('/')->getContent();

            foreach (['osim-menu-desktop', 'osim-menu-ponsel'] as $kelas) {
                $hitung = $this->hitungMenu($html, $kelas);
                $this->assertCount($mode ? 4 : 3, $hitung, $kelas);
                $this->assertSame([1], array_values(array_unique($hitung)), $kelas);
            }
        }
    }

    public function test_menu_kandidat_tanggapi_pertanyaan_tersembunyi_saat_mode_nonaktif(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $this->actingAs(User::factory()->create(['role' => \App\Enums\Peran::Kandidat, 'candidate_id' => $calon->id]));

        $this->get('/')->assertSee('Tanggapi pertanyaan');

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());
        $this->get('/')->assertDontSee('Tanggapi pertanyaan');
    }

    public function test_navigasi_dirender_dari_status_mode_dengan_penanda_halaman_aktif(): void
    {
        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());

        $html = $this->get('/struktur')->assertOk()->getContent();
        $aktif = fn (string $rute) => preg_match('#href="'.preg_quote(route($rute), '#').'" wire:navigate\s+aria-current="page"#', $html);

        $this->assertSame(1, $aktif('struktur'));
        $this->assertSame(0, $aktif('saran'));
    }
}
