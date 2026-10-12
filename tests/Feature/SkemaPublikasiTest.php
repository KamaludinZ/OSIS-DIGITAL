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
}
