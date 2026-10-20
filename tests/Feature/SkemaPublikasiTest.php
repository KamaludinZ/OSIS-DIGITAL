<?php

namespace Tests\Feature;

use App\Enums\StatusProgramKerja;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\WorkProgram;
use Database\Seeders\PublikasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaPublikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_publikasi_mengisi_relasi_divisi(): void
    {
        $this->seed(PublikasiSeeder::class);

        $this->assertSame(5, Announcement::tayang()->count());
        $this->assertSame('Divisi Lingkungan', WorkProgram::where('nama', 'Bank Sampah Sekolah')->first()->divisi->nama);
        $this->assertSame(StatusProgramKerja::Selesai, WorkProgram::where('nama', 'Kelas Inspirasi Alumni')->first()->status);
        $this->assertSame(3, WorkProgram::where('unggulan', true)->count());
    }

    public function test_scope_tayang_dan_mendatang(): void
    {
        Announcement::factory()->create(['judul' => 'Lama', 'dipublikasikan_pada' => now()->subDays(3)]);
        Announcement::factory()->create(['judul' => 'Baru', 'dipublikasikan_pada' => now()->subDay()]);
        Announcement::factory()->draf()->create(['judul' => 'Draf']);
        Announcement::factory()->create(['judul' => 'Terjadwal', 'dipublikasikan_pada' => now()->addDay()]);

        $this->assertSame(['Baru', 'Lama'], Announcement::tayang()->pluck('judul')->all());

        Event::factory()->create(['nama' => 'Lalu', 'tanggal_mulai' => today()->subDays(5), 'tanggal_selesai' => today()->subDays(4)]);
        Event::factory()->create(['nama' => 'Nanti', 'tanggal_mulai' => today()->addDays(5), 'tanggal_selesai' => today()->addDays(5)]);
        Event::factory()->create(['nama' => 'Hari ini', 'tanggal_mulai' => today()->subDay(), 'tanggal_selesai' => today()]);

        $this->assertSame(['Hari ini', 'Nanti'], Event::mendatang()->pluck('nama')->all());
    }

    public function test_agenda_per_periode_dan_bentrok_hanya_dalam_periode_aktif(): void
    {
        $lama = \App\Models\OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $tanggal = now()->addDays(5)->toDateString();
        \App\Models\Event::factory()->create(['nama' => 'Agenda Lama', 'tanggal_mulai' => $tanggal, 'tanggal_selesai' => $tanggal, 'tempat' => 'Aula']);

        app(\App\Services\LayananPeriode::class)->aktifkan(\App\Models\OsisPeriod::factory()->tahun(2026)->create());
        $baru = \App\Models\Event::factory()->create(['nama' => 'Agenda Baru', 'tanggal_mulai' => $tanggal, 'tanggal_selesai' => $tanggal, 'tempat' => 'Aula']);

        $this->assertSame(['Agenda Baru'], \App\Models\Event::pluck('nama')->all());
        $this->assertSame(1, \App\Models\Event::diPeriode($lama)->count());
        // Bentrok dihitung di periode aktif saja.
        $this->assertTrue(\App\Models\Event::bentrok($tanggal, $tanggal, 'Aula')->contains('id', $baru->id));
        $this->assertSame(1, \App\Models\Event::bentrok($tanggal, $tanggal, 'Aula')->count());
    }
}
