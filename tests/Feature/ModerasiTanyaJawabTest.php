<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\ModerasiTanyaJawab;
use App\Models\Candidate;
use App\Models\CandidateQuestion;
use App\Models\User;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModerasiTanyaJawabTest extends TestCase
{
    use RefreshDatabase;

    public function test_antrean_moderasi_lintas_kandidat(): void
    {
        $this->seed(PemilihanSeeder::class);
        [$satu, $dua] = Candidate::orderBy('nomor_urut')->take(2)->get();
        $belum = CandidateQuestion::factory()->create(['candidate_id' => $satu->id, 'pertanyaan' => 'Pertanyaan belum dijawab', 'jawaban' => null]);
        $terjawab = CandidateQuestion::factory()->create(['candidate_id' => $dua->id, 'pertanyaan' => 'Pertanyaan terjawab', 'jawaban' => 'Ini jawabannya.']);

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/moderasi-tanya-jawab')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/moderasi-tanya-jawab')->assertOk();

        Livewire::test(ModerasiTanyaJawab::class)
            ->assertCanSeeTableRecords([$belum, $terjawab])
            ->assertTableColumnStateSet('status_jawab', 'Belum dijawab', $belum)
            ->filterTable('jawaban', true)
            ->assertCanSeeTableRecords([$terjawab])->assertCanNotSeeTableRecords([$belum])
            ->resetTableFilters()
            ->filterTable('candidate_id', $satu->id)
            ->assertCanSeeTableRecords([$belum])->assertCanNotSeeTableRecords([$terjawab])
            ->resetTableFilters()
            ->assertTableActionHidden('setujui', $belum)
            ->callTableAction('tolak', $belum)
            ->assertTableActionVisible('setujui', $belum);

        $this->assertFalse($belum->fresh()->ditampilkan);

        Livewire::test(ModerasiTanyaJawab::class)->callTableAction('setujui', $belum);
        $this->assertTrue($belum->fresh()->ditampilkan);
    }
}
