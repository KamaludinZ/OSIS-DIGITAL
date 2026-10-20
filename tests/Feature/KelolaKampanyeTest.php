<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\RelationManagers\JadwalRelationManager;
use App\Filament\Resources\Candidates\RelationManagers\MateriRelationManager;
use App\Filament\Resources\Candidates\RelationManagers\PertanyaanRelationManager;
use App\Models\Candidate;
use App\Models\CandidateQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaKampanyeTest extends TestCase
{
    use RefreshDatabase;

    private function manajer(string $kelas, Candidate $calon)
    {
        return Livewire::test($kelas, ['ownerRecord' => $calon, 'pageClass' => EditCandidate::class]);
    }

    public function test_panitia_menambah_materi_dan_jadwal(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $calon = Candidate::factory()->create();
        $lain = Candidate::factory()->create(['election_id' => $calon->election_id]);

        $this->manajer(MateriRelationManager::class, $calon)
            ->callTableAction('create', data: ['tipe' => 'janji', 'deskripsi' => 'Jumat bersih setiap pekan', 'urutan' => 1])->assertHasNoTableActionErrors()
            ->callTableAction('create', data: ['tipe' => 'video', 'url_konten' => 'https://youtu.be/dQw4w9WgXcQ', 'urutan' => 2])->assertHasNoTableActionErrors()
            ->callTableAction('create', data: ['tipe' => 'video', 'url_konten' => ''])->assertHasTableActionErrors(['url_konten' => 'required']);

        $this->manajer(JadwalRelationManager::class, $calon)
            ->callTableAction('create', data: ['tanggal' => '2026-10-15', 'waktu' => '09:00', 'tempat' => 'Aula'])->assertHasNoTableActionErrors();
        $this->manajer(JadwalRelationManager::class, $lain)
            ->callTableAction('create', data: ['tanggal' => '2026-10-15', 'waktu' => '09:00', 'tempat' => 'Aula'])->assertHasTableActionErrors(['tempat' => 'unique']);

        $this->get('/kandidat/'.$calon->id.'?tab=materi')->assertSee('Jumat bersih setiap pekan')->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $this->get('/kandidat/'.$calon->id.'?tab=jadwal')->assertSee('Aula');
    }

    public function test_moderasi_tanya_jawab(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $calon = Candidate::factory()->create();
        $q = CandidateQuestion::factory()->create(['candidate_id' => $calon->id, 'pertanyaan' => 'Pertanyaan tidak pantas sekali', 'ditampilkan' => true]);

        $this->manajer(PertanyaanRelationManager::class, $calon)
            ->assertCanSeeTableRecords([$q])
            ->callTableBulkAction('sembunyikan', [$q]);

        $this->assertFalse($q->fresh()->ditampilkan);
        $this->get('/kandidat/'.$calon->id.'?tab=tanya-jawab')->assertDontSee('Pertanyaan tidak pantas sekali');
    }

    public function test_data_kampanye_tercatat_di_periode_kandidat(): void
    {
        $periode = \App\Models\OsisPeriod::factory()->tahun(2026)->aktif()->create();
        $calon = \App\Models\Candidate::factory()->create(['periode_id' => $periode->id]);

        $materi = \App\Models\CampaignMaterial::create(['candidate_id' => $calon->id, 'tipe' => 'janji', 'deskripsi' => 'Janji kampanye.']);
        $tanya = \App\Models\CandidateQuestion::factory()->create(['candidate_id' => $calon->id]);

        $this->assertSame($periode->id, $materi->periode_id);
        $this->assertSame($periode->id, $tanya->periode_id);
        $this->assertTrue($tanya->periode->is($periode));
        foreach (['campaign_materials', 'campaign_schedules', 'candidate_questions'] as $tabel) {
            $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns($tabel, ['periode_id']), $tabel);
        }
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('candidate_questions', ['ditampilkan', 'jawaban']));
    }

    public function test_global_scope_periode_pada_data_kampanye(): void
    {
        $lama = \App\Models\OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $calonLama = \App\Models\Candidate::factory()->create(['periode_id' => $lama->id]);
        $tanyaLama = \App\Models\CandidateQuestion::factory()->create(['candidate_id' => $calonLama->id]);

        app(\App\Services\LayananPeriode::class)->aktifkan($baru = \App\Models\OsisPeriod::factory()->tahun(2026)->create());
        $calonBaru = \App\Models\Candidate::factory()->create(['periode_id' => $baru->id]);
        $tanyaBaru = \App\Models\CandidateQuestion::factory()->create(['candidate_id' => $calonBaru->id]);

        $this->assertSame([$tanyaBaru->id], \App\Models\CandidateQuestion::pluck('id')->all());
        $this->assertSame(1, \App\Models\CandidateQuestion::diPeriode($lama)->count());

        // Arsip hanya-baca: tanya jawab periode lama tidak bisa dimoderasi/diubah.
        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        \App\Models\CandidateQuestion::semuaPeriode()->find($tanyaLama->id)->update(['ditampilkan' => false]);
    }
}
