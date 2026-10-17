<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Services\SesiPemilih;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;
use Tests\TestCase;

/** Uji regresi temuan audit keamanan. */
class AuditKeamananTest extends TestCase
{
    use RefreshDatabase;

    private function coba(string $kode): bool
    {
        try {
            app(SesiPemilih::class)->masukDenganKode($kode);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    public function test_kode_sendiri_tidak_bisa_mengosongkan_batas_tebakan(): void
    {
        $milikSendiri = Voter::factory()->berkode('111111')->create();

        // Penyerang berganti sesi & menyelipkan kode miliknya tiap 4 tebakan.
        for ($putaran = 0; $putaran < 5; $putaran++) {
            session()->flush();
            session()->regenerate();
            foreach (range(1, 4) as $i) {
                $this->assertFalse($this->coba(sprintf('9%05d', $putaran * 10 + $i)));
            }
            // Putaran 1–4 masih boleh masuk; setelah 20 tebakan salah dari IP ini semua ditahan.
            $this->assertSame($putaran < 4, $this->coba('111111'), 'putaran '.$putaran);
        }
    }

    public function test_batas_per_sesi_tidak_mengunci_seluruh_lab(): void
    {
        Voter::factory()->berkode('222222')->create();

        foreach (range(1, 5) as $i) {
            $this->coba('00000'.$i); // satu komputer salah ketik 5 kali
        }
        $this->assertFalse($this->coba('222222'));

        // Komputer lain di lab yang sama (IP sama, sesi berbeda) tetap bisa masuk.
        session()->flush();
        session()->regenerate();
        $this->assertTrue($this->coba('222222'));
    }

    public function test_panitia_diberi_tahu_saat_tebakan_melonjak(): void
    {
        $panitia = User::factory()->create(['role' => Peran::Panitia]);

        for ($i = 0; $i < 45; $i++) {
            if ($i % 5 === 0) {
                session()->flush();
                session()->regenerate();
                \Illuminate\Support\Facades\RateLimiter::clear('kode-pilih:127.0.0.1'); // abaikan batas per menit untuk simulasi 1 jam
            }
            $this->coba(sprintf('8%05d', $i));
        }

        $this->assertSame(1, $panitia->notifications()->count());
        $this->assertStringContainsString('kode pilih salah', $panitia->notifications()->first()->data['title']);
    }

    public function test_sesi_bilik_suara_kedaluwarsa_setelah_15_menit(): void
    {
        $pemilih = Voter::factory()->berkode('333333')->create();
        $this->assertTrue($this->coba('333333'));
        $this->assertSame($pemilih->id, app(SesiPemilih::class)->pemilih()?->id);

        $this->travel(16)->minutes();
        $this->assertNull(app(SesiPemilih::class)->pemilih());
        $this->assertNull(session(SesiPemilih::KUNCI_PEMILIH));
    }

    public function test_status_server_tidak_bisa_diubah_dari_peramban(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $calon = Candidate::first();

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Volt::test('bilik-suara')->set('pemilihan', ['id' => 999, 'status' => 'berjalan']);
    }

    public function test_profil_kandidat_terkunci(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Volt::test('kandidat.profil', ['id' => Candidate::first()->id])->set('calon.id', 999);
    }

    public function test_suara_hanya_ke_pemilihan_aktif(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $lain = Election::factory()->create(['judul' => 'Pemilihan lain', 'mulai' => now()->subYear(), 'selesai' => now()->subYear()->addDay()]);
        $calonLain = Candidate::factory()->create(['election_id' => $lain->id]);
        $pemilih = Voter::factory()->create();

        // Kandidat dari pemilihan lain ditolak walau id-nya dikirim langsung.
        $this->expectException(\App\Exceptions\SuaraDitolak::class);
        app(\App\Services\LayananPemilihan::class)->kirimSuara($pemilih, Election::aktif(), $calonLain->id);
    }

    public function test_header_keamanan(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    }

    public function test_kata_kunci_pencarian_dipotong(): void
    {
        \App\Models\Announcement::factory()->create(['judul' => str_repeat('a', 100), 'dipublikasikan' => true, 'dipublikasikan_pada' => now()->subDay()]);

        // Kata kunci 101+ karakter dipotong menjadi 100 sebelum menjadi pola LIKE.
        $this->assertCount(1, app(\App\Services\LayananPublikasi::class)->cari(str_repeat('a', 100).str_repeat('b', 5000))['pengumuman']);
    }

    public function test_policy_data_pemilihan(): void
    {
        $calon = Candidate::factory()->create();
        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);
        $panitia = User::factory()->create(['role' => Peran::Panitia]);

        foreach ([Election::class, Candidate::class, \App\Models\CampaignMaterial::class, \App\Models\CampaignSchedule::class, \App\Models\CandidateQuestion::class] as $model) {
            $this->assertFalse($pengurus->can('create', $model), $model);
            $this->assertTrue($panitia->can('create', $model), $model);
        }
        $this->assertFalse($pengurus->can('update', $calon));
        $this->assertFalse(User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $calon->id])->can('update', $calon));
    }

    public function test_unggahan_svg_ditolak(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $e = Election::factory()->create(['format_kandidat' => 'ketua']);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $svg = \Illuminate\Http\UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        \Livewire\Livewire::test(\App\Filament\Resources\Candidates\Pages\CreateCandidate::class)
            ->fillForm(['election_id' => $e->id, 'nomor_urut' => 1, 'nama_ketua' => 'Uji', 'foto_url' => $svg])
            ->call('create')
            ->assertHasFormErrors(['foto_url']);
    }

    public function test_masuk_kode_mengganti_id_sesi_tanpa_mengganti_token_csrf(): void
    {
        // Regresi: regenerate() mengganti token CSRF sehingga halaman Livewire yang terbuka kena 419.
        Voter::factory()->berkode('444444')->create(['tanggal_lahir' => '2012-05-05', 'nomor_induk' => '0091112223']);
        session()->start();
        [$id, $token] = [session()->getId(), session()->token()];

        $this->assertTrue($this->coba('444444'));
        $this->assertNotSame($id, session()->getId());
        $this->assertSame($token, session()->token());

        $id = session()->getId();
        app(SesiPemilih::class)->verifikasiPenanya('siswa', '0091112223', '2012-05-05');
        $this->assertNotSame($id, session()->getId());
        $this->assertSame($token, session()->token());
    }

    public function test_ip_asli_terbaca_di_balik_proxy_dan_tidak_bisa_dipalsukan(): void
    {
        \Illuminate\Support\Facades\Route::get('/_uji-ip', fn () => request()->ip());

        // Lewat Traefik (IP Docker 172.18.0.2): IP pengunjung asli dari X-Forwarded-For dipakai.
        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_uji-ip')->assertSeeText('203.0.113.7');

        // Pengunjung menyisipkan X-Forwarded-For palsu: yang terbaca tetap IP aslinya (dari Traefik).
        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])->withHeader('X-Forwarded-For', '1.2.3.4, 203.0.113.7')
            ->get('/_uji-ip')->assertSeeText('203.0.113.7');

        // Langsung dari internet (bukan proxy tepercaya): header diabaikan.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])->withHeader('X-Forwarded-For', '1.2.3.4')
            ->get('/_uji-ip')->assertSeeText('198.51.100.9');
    }

    public function test_perintah_buat_admin_produksi(): void
    {
        $this->artisan('osim:buat-admin', ['email' => 'Admin@MTsN2.sch.id', '--nama' => 'Pak Admin'])
            ->expectsQuestion('Kata sandi (minimal 12 karakter, kosongkan untuk dibuatkan otomatis)', 'sandi-sangat-kuat-1')
            ->expectsOutputToContain('Akun admin siap: admin@mtsn2.sch.id')
            ->assertSuccessful();

        $akun = User::firstWhere('email', 'admin@mtsn2.sch.id');
        $this->assertSame(Peran::Admin, $akun->role);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('sandi-sangat-kuat-1', $akun->password));

        $this->artisan('osim:buat-admin', ['email' => 'x@y.id'])->expectsQuestion('Kata sandi (minimal 12 karakter, kosongkan untuk dibuatkan otomatis)', 'pendek')
            ->assertFailed();
    }
}
