<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Announcement;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_bisa_dijalankan_berulang_dan_akun_contoh_bisa_masuk(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(4, User::count()); // 3 staf + 1 akun kandidat
        $this->assertSame(1, \App\Models\Voter::where('kode_pilih', '246810')->count());
        $this->assertSame(3, Candidate::count());
        $this->assertSame(5, Announcement::count());

        $panitia = User::where('email', 'panitia@osis.test')->first();
        $this->assertTrue(Hash::check('password', $panitia->password));
        $this->assertFalse(User::where('role', Peran::Siswa)->exists());
        $this->assertSame(Peran::Panitia, User::where('email', 'panitia@osis.test')->first()->role);
    }

    public function test_seeder_menyiapkan_arsip_periode_lama_yang_tetap_tersimpan(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->seed();
        $this->seed();

        $aktif = \App\Models\OsisPeriod::aktif();
        $arsip = \App\Models\OsisPeriod::status(\App\Enums\StatusPeriode::Arsip)->sole();

        $this->assertTrue($aktif->mode_pemilihan);
        // Arsip tersimpan & terbaca lewat relasi tanpa batas periode aktif.
        $this->assertSame('3:4', $arsip->struktur->rasio);
        $this->assertSame(1, $arsip->suggestions()->count());
        $this->assertSame(1, $arsip->socialLinks()->count());
        // Tidak bocor ke periode aktif.
        $this->assertSame(0, \App\Models\Suggestion::count());
        $this->assertNotContains('@osim.arsip.contoh', \App\Models\SocialLink::pluck('nama_akun'));
        // Halaman publik menawarkan arsipnya.
        $this->get('/struktur')->assertSee($arsip->nama);
    }
}
