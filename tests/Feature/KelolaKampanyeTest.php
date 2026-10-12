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
}
