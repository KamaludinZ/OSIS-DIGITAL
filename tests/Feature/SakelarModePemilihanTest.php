<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\OsisPeriod;
use App\Models\User;
use App\Services\LayananModePemilihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Alur lengkap sakelar mode pemilihan: dari tombol di Ruang OSIM sampai halaman publik. */
class SakelarModePemilihanTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = OsisPeriod::factory()->tahun(2026)->aktif()->create(['mode_pemilihan' => true]);
        $this->seed([\Database\Seeders\PemilihanSeeder::class]);
    }

    /** Ambil snapshot komponen Livewire dari HTML halaman yang sudah dirender. */
    private function snapshot(string $html): string
    {
        preg_match('/wire:snapshot="([^"]+)"/', $html, $cocok);

        return html_entity_decode($cocok[1]);
    }

    public function test_alur_matikan_lalu_nyalakan_dari_ruang_osim(): void
    {
        $this->get('/bilik-suara')->assertOk();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)
            ->callAction('ubahMode')->assertNotified('Mode pemilihan dimatikan');
        auth()->logout();

        $this->get('/bilik-suara')->assertStatus(423);
        $this->get('/kandidat')->assertStatus(423);
        $this->get('/hasil')->assertStatus(423);

        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        Livewire::test(\App\Filament\Resources\Elections\Pages\ListElections::class)
            ->callAction('ubahMode')->assertNotified('Mode pemilihan dinyalakan');
        auth()->logout();

        $this->get('/bilik-suara')->assertOk();
        $this->get('/kandidat')->assertOk();
        $this->get('/hasil')->assertOk();
    }

    public function test_halaman_yang_sudah_terbuka_tidak_bisa_beraksi_setelah_mode_dimatikan(): void
    {
        $snapshot = $this->snapshot($this->get('/bilik-suara')->assertOk()->getContent());
        $kirim = fn () => $this->withHeaders(['X-Livewire' => 'true'])->postJson(app('livewire')->getUpdateUri(), [
            'components' => [['snapshot' => $snapshot, 'updates' => [], 'calls' => [['path' => '', 'method' => '$refresh', 'params' => []]]]],
        ]);

        $kirim()->assertOk();

        app(LayananModePemilihan::class)->ubah(false, User::factory()->create());
        // Di produksi tiap permintaan proses baru; di uji, status Livewire antarpermintaan dikosongkan manual.
        app('livewire')->flushState();

        $kirim()->assertStatus(423);
    }

    public function test_periode_baru_yang_diaktifkan_mulai_tanpa_mode_pemilihan(): void
    {
        $baru = OsisPeriod::factory()->tahun(2027)->create();
        app(\App\Services\LayananPeriode::class)->aktifkan($baru);

        $this->get('/bilik-suara')->assertStatus(423);
        $this->assertFalse(app(LayananModePemilihan::class)->status()['aktif']);
        $this->assertSame('2027/2028', app(LayananModePemilihan::class)->status()['periode']);
    }

    public function test_status_mode_tercatat_per_periode(): void
    {
        $panitia = User::factory()->create(['role' => Peran::Panitia]);
        $this->actingAs($panitia)->patchJson('/mode-pemilihan', ['aktif' => false])->assertOk();

        $this->travel(1)->hours();
        $this->periode->refresh();

        $this->assertFalse($this->periode->mode_pemilihan);
        $this->assertTrue($this->periode->pengubahMode->is($panitia));
        $this->assertTrue($this->periode->mode_diubah_pada->lessThan(now()));

        // Arsip menyimpan mode terakhirnya, tidak memengaruhi situs setelah periode lain aktif.
        $baru = OsisPeriod::factory()->tahun(2027)->create();
        app(\App\Services\LayananPeriode::class)->aktifkan($baru);
        app(LayananModePemilihan::class)->ubah(true, $panitia);

        $this->assertFalse($this->periode->fresh()->mode_pemilihan);
        $this->assertTrue(app(LayananModePemilihan::class)->aktif());
    }
}
