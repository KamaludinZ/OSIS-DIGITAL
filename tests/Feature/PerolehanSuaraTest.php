<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\PerolehanSuara;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Vote;
use App\Models\Voter;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PerolehanSuaraTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_perolehan_hanya_untuk_panitia_dan_admin(): void
    {
        $this->seed(PemilihanSeeder::class);
        $this->get('/admin/perolehan-suara')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/perolehan-suara')->assertForbidden();

        $calon = Candidate::where('nomor_urut', 2)->first();
        Vote::create(['election_id' => $calon->election_id, 'candidate_id' => $calon->id, 'voter_id' => Voter::factory()->create()->id, 'kode_bukti' => 'OSIM-AAAA-BBBB']);

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/perolehan-suara')->assertOk()
            ->assertSee('Perolehan Suara')
            ->assertSeeInOrder(['No. 2', $calon->nama_ketua, '1', 'suara', '100%'])
            ->assertSee('Hasil publik');
    }

    public function test_publikasi_dari_panel_hanya_setelah_ditutup(): void
    {
        $this->seed(PemilihanSeeder::class);
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        $pemilihan = Election::first();

        Livewire::test(PerolehanSuara::class)
            ->assertActionHasLabel('publikasi', 'Publikasikan hasil')
            ->callAction('publikasi')
            ->assertNotified('Hasil hanya bisa dipublikasikan setelah pemilihan ditutup.');
        $this->assertFalse($pemilihan->fresh()->hasil_publik);

        $pemilihan->update(['status' => 'selesai', 'selesai' => now()->subMinute()]);
        Livewire::test(PerolehanSuara::class)
            ->callAction('publikasi')
            ->assertNotified('Hasil dipublikasikan')
            ->assertActionHasLabel('publikasi', 'Tarik publikasi')
            ->assertActionHasUrl('cetak', route('hasil.laporan'));
        $this->assertTrue($pemilihan->fresh()->hasil_publik);
        $this->assertNotNull($pemilihan->fresh()->hasil_dipublikasikan_pada);
        $this->assertTrue($pemilihan->fresh()->pempublikasi->is(auth()->user()));

        // Ditarik: jejak publikasi dikosongkan.
        Livewire::test(PerolehanSuara::class)->callAction('publikasi')->assertNotified('Publikasi ditarik');
        $this->assertNull($pemilihan->fresh()->hasil_dipublikasikan_pada);
        $this->assertNull($pemilihan->fresh()->hasil_dipublikasikan_oleh);
    }

    public function test_endpoint_agregasi_hasil(): void
    {
        $this->seed(PemilihanSeeder::class);
        $pemilihan = Election::first();
        [$satu, $dua] = Candidate::orderBy('nomor_urut')->take(2)->get();
        foreach ([$satu, $satu, $dua] as $calon) {
            app(\App\Services\LayananPemilihan::class)->kirimSuara(Voter::factory()->create(), $pemilihan, $calon->id);
        }

        // Publik: belum dipublikasikan -> ditolak.
        $this->getJson('/hasil/rekap.json')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->getJson('/hasil/rekap.json')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('suara_masuk', 3)
            ->assertJsonPath('kandidat.0.nomor_urut', 1)
            ->assertJsonPath('kandidat.0.suara', 2)
            ->assertJsonPath('kandidat.1.suara', 1)
            ->assertJsonMissingPath('kandidat.0.id');

        auth()->logout();
        $pemilihan->update(['status' => 'selesai', 'selesai' => now()->subMinute(), 'hasil_publik' => true]);
        $this->getJson('/hasil/rekap.json')->assertOk()->assertJsonPath('pemenang', 1);
    }
}
