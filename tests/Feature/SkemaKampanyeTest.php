<?php

namespace Tests\Feature;

use App\Enums\TipeMateri;
use App\Models\Candidate;
use App\Models\CandidateQuestion;
use Database\Seeders\KampanyeSeeder;
use Database\Seeders\PemilihanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaKampanyeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_kampanye_mengisi_relasi_kandidat_dan_bisa_diulang(): void
    {
        $this->seed([PemilihanSeeder::class, KampanyeSeeder::class, KampanyeSeeder::class]);

        $calon = Candidate::where('nomor_urut', 1)->first();

        $this->assertSame(6, $calon->materials()->count());
        $this->assertSame(TipeMateri::Poster, $calon->materials->first()->tipe);
        $this->assertSame(['Aula Utama', 'Masjid Sekolah', 'Kantin Sekolah'], $calon->schedules->pluck('tempat')->all());
        $this->assertSame(2, $calon->questions()->count());
        $this->assertSame('Citra Lestari', $calon->questions()->whereNotNull('jawaban')->first()->penanya->name);
    }

    public function test_hapus_kandidat_ikut_menghapus_data_kampanye(): void
    {
        $q = CandidateQuestion::factory()->dijawab()->create();

        $q->candidate->delete();

        $this->assertDatabaseCount('candidate_questions', 0);
    }

    public function test_jadwal_kampanye_tidak_boleh_bentrok_tempat_dan_waktu(): void
    {
        $slot = ['tanggal' => today()->addDay()->toDateString(), 'waktu' => '10:00', 'tempat' => 'Aula Utama'];
        \App\Models\CampaignSchedule::factory()->create($slot);

        // Jam lain di tempat yang sama boleh.
        \App\Models\CampaignSchedule::factory()->create(['waktu' => '13:00'] + $slot);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        \App\Models\CampaignSchedule::factory()->create($slot);
    }

    public function test_pertanyaan_bisa_disembunyikan_moderasi(): void
    {
        $tampil = CandidateQuestion::factory()->create();
        CandidateQuestion::factory()->for($tampil->candidate)->create(['ditampilkan' => false]);

        $this->assertTrue($tampil->fresh()->ditampilkan);
        $this->assertSame([$tampil->id], $tampil->candidate->questions()->tampil()->pluck('id')->all());
    }
}
