<?php

namespace Tests\Feature;

use App\Models\Concerns\IkutPeriode;
use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PeriodeScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('data_uji_periode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->nullable();
            $table->string('isi');
            $table->timestamps();
        });
    }

    public function test_periode_aktif_diambil_dan_diperbarui_setelah_berganti(): void
    {
        $this->assertNull(OsisPeriod::aktif());

        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $this->assertTrue(OsisPeriod::aktif()->is($lama));

        $lama->update(['status' => 'arsip']);
        $baru = OsisPeriod::factory()->tahun(2026)->aktif()->create();

        $this->assertTrue(OsisPeriod::aktif()->is($baru));
        $this->assertTrue($baru->adalahAktif());
        $this->assertTrue($lama->fresh()->adalahArsip());
    }

    public function test_global_scope_hanya_menampilkan_data_periode_aktif(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        DataUjiPeriode::create(['isi' => 'saran lama']);
        $this->assertSame($lama->id, DataUjiPeriode::first()->periode_id);

        $lama->update(['status' => 'arsip']);
        $baru = OsisPeriod::factory()->tahun(2026)->aktif()->create();
        DataUjiPeriode::create(['isi' => 'saran baru']);

        $this->assertSame(['saran baru'], DataUjiPeriode::pluck('isi')->all());
        $this->assertSame(['saran lama'], DataUjiPeriode::diPeriode($lama)->pluck('isi')->all());
        $this->assertSame(2, DataUjiPeriode::semuaPeriode()->count());
        $this->assertTrue(DataUjiPeriode::first()->periode->is($baru));
    }

    public function test_tanpa_periode_aktif_tidak_ada_data_yang_tampil(): void
    {
        $p = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        DataUjiPeriode::create(['isi' => 'saran']);

        $p->update(['status' => 'arsip']);

        $this->assertSame(0, DataUjiPeriode::count());
        $this->assertSame(1, DataUjiPeriode::semuaPeriode()->count());
    }

    public function test_data_periode_arsip_tidak_bisa_diubah_atau_dihapus(): void
    {
        $lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $data = DataUjiPeriode::create(['isi' => 'saran lama']);
        $lama->update(['status' => 'arsip']);
        $baru = OsisPeriod::factory()->tahun(2026)->aktif()->create();

        $data = DataUjiPeriode::diPeriode($lama)->first();
        foreach ([
            fn () => $data->update(['isi' => 'diubah']),
            fn () => $data->update(['periode_id' => $baru->id]),
            fn () => $data->delete(),
            fn () => DataUjiPeriode::create(['isi' => 'baru', 'periode_id' => $lama->id]),
        ] as $i => $aksi) {
            try {
                $aksi();
                $this->fail('Aksi ke-'.$i.' seharusnya ditolak.');
            } catch (\App\Exceptions\PeriodeDitolak) {
            }
        }

        $this->assertSame('saran lama', DataUjiPeriode::diPeriode($lama)->value('isi'));
        // Data periode aktif tetap bisa dikelola.
        DataUjiPeriode::create(['isi' => 'baru'])->update(['isi' => 'baru diubah']);
        $this->assertSame(['baru diubah'], DataUjiPeriode::pluck('isi')->all());
    }
}

class DataUjiPeriode extends Model
{
    use IkutPeriode;

    protected $table = 'data_uji_periode';

    protected $guarded = [];
}
