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
}
