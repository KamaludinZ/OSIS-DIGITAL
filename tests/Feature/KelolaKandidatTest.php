<?php

namespace Tests\Feature;

use App\Enums\FormatKandidat;
use App\Enums\Peran;
use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Candidate;
use App\Models\CandidateQuestion;
use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class KelolaKandidatTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_menambah_kandidat_ketua_saja_dan_tampil_tanpa_wakil(): void
    {
        $e = Election::factory()->create(['format_kandidat' => FormatKandidat::Ketua]);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/kandidat')->assertOk();

        Livewire::test(CreateCandidate::class)
            ->fillForm(['election_id' => $e->id, 'nomor_urut' => 1, 'nama_ketua' => 'Nadia Rahma', 'kelas_ketua' => 'VIII-A'])
            ->call('create')
            ->assertHasNoFormErrors();

        $calon = Candidate::firstWhere('nama_ketua', 'Nadia Rahma');
        $this->assertNull($calon->nama_wakil);
        $this->assertFalse($calon->berpasangan());

        auth()->logout();
        $this->get('/kandidat')->assertOk()->assertSee('Kartu Calon Ketua')->assertSee('Kenali Calon Ketua')->assertDontSee('Calon Wakil');
        $this->get('/kandidat/'.$calon->id)->assertOk()->assertSee('Nadia Rahma')->assertSee('Profil Calon 1')->assertDontSee('Wakil ·');
        $this->get('/bilik-suara')->assertOk()->assertDontSee('Pasangan Calon');
    }

    public function test_format_berpasangan_mewajibkan_wakil_dan_nomor_urut_unik(): void
    {
        $e = Election::factory()->create();
        Candidate::factory()->create(['election_id' => $e->id, 'nomor_urut' => 1]);
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(CreateCandidate::class)
            ->fillForm(['election_id' => $e->id, 'nomor_urut' => 1, 'nama_ketua' => 'Tanpa Wakil'])
            ->call('create')
            ->assertHasFormErrors(['nama_wakil' => 'required', 'nomor_urut']);

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/kandidat')->assertForbidden();
        $this->get('/admin/periode-pemilihan')->assertForbidden();
    }

    public function test_admin_membuat_akun_kandidat_dan_hanya_admin(): void
    {
        $calon = Candidate::factory()->create(['election_id' => Election::factory()]);
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Rizky', 'email' => 'Rizky@Osim.test', 'role' => 'kandidat', 'candidate_id' => $calon->id, 'password' => 'rahasia123'])
            ->call('create')
            ->assertHasNoFormErrors();

        $akun = User::firstWhere('email', 'rizky@osim.test');
        $this->assertSame(Peran::Kandidat, $akun->role);
        $this->assertSame($calon->id, $akun->candidate_id);

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/akun')->assertForbidden();
    }

    public function test_kandidat_masuk_dan_menanggapi_pertanyaan_miliknya_saja(): void
    {
        $e = Election::factory()->create();
        [$satu, $dua] = Candidate::factory()->count(2)->sequence(['nomor_urut' => 1], ['nomor_urut' => 2])->create(['election_id' => $e->id]);
        $tanya = CandidateQuestion::factory()->create(['candidate_id' => $satu->id, 'jawaban' => null]);
        $lain = CandidateQuestion::factory()->create(['candidate_id' => $dua->id, 'jawaban' => null]);
        $akun = User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $satu->id, 'password' => 'password']);

        Volt::test('auth.login')->set('email', $akun->email)->set('password', 'password')->call('login')
            ->assertRedirect(route('kandidat.profil', ['id' => $satu->id, 'tab' => 'tanya-jawab']));

        $this->actingAs($akun);
        $this->get('/admin')->assertRedirect(route('kandidat.profil', ['id' => $satu->id, 'tab' => 'tanya-jawab']));

        Volt::test('kandidat.profil', ['id' => $satu->id])->set('tab', 'tanya-jawab')
            ->assertSee('Kamu masuk sebagai kandidat nomor 1')
            ->assertDontSee('Konfirmasi dulu identitasmu')
            ->set('tanggapan.'.$tanya->id, 'Kami akan adakan jadwal piket bergilir.')
            ->call('tanggapi', $tanya->id)
            ->assertSee('Tanggapan tersimpan');
        $this->assertSame('Kami akan adakan jadwal piket bergilir.', $tanya->fresh()->jawaban);
        $this->assertNotNull($tanya->fresh()->dijawab_pada);

        // Profil kandidat lain: tidak bisa menanggapi, tidak bisa bertanya.
        Volt::test('kandidat.profil', ['id' => $dua->id])->set('tab', 'tanya-jawab')
            ->assertSee('Akun kandidat hanya bisa menanggapi pertanyaan di profilnya sendiri')
            ->set('tanggapan.'.$lain->id, 'Menyusup')
            ->call('tanggapi', $lain->id)
            ->assertForbidden();
        $this->assertNull($lain->fresh()->jawaban);
    }
}
