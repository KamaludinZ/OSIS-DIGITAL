<?php

namespace Tests\Feature;

use App\Models\OsisPeriod;
use App\Models\Suggestion;
use App\Services\LayananSaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class KirimSaranTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periode = OsisPeriod::factory()->tahun(2026)->aktif()->create();
    }

    public function test_kirim_saran_anonim_tanpa_jejak_pengirim(): void
    {
        Volt::test('saran')
            ->set('isi', 'pendek')->call('kirim')->assertHasErrors(['isi' => 'min'])
            ->set('isi', '  Tolong tambah rak sepeda di dekat gerbang belakang.  ')
            ->set('nama', 'Diabaikan')
            ->call('kirim')
            ->assertHasNoErrors()->assertSet('isi', '')->assertSee('Saran terkirim');

        $saran = Suggestion::sole();
        $this->assertSame('Tolong tambah rak sepeda di dekat gerbang belakang.', $saran->isi);
        $this->assertNull($saran->nama);
        $this->assertNull($saran->kelas);
        $this->assertTrue($saran->anonim());
        $this->assertSame('Anonim', $saran->pengirim());
        $this->assertSame($this->periode->id, $saran->periode_id);
    }

    public function test_kirim_saran_bernama(): void
    {
        Volt::test('saran')
            ->set('bernama', true)
            ->set('isi', 'Usul lomba kebersihan kelas tiap bulan.')
            ->call('kirim')->assertHasErrors(['nama' => 'required'])
            ->set('nama', 'Galih Prakoso')->set('kelas', 'ix-a')
            ->call('kirim')->assertHasNoErrors()->assertSet('bernama', false);

        $saran = Suggestion::sole();
        $this->assertSame('Galih Prakoso · IX-A', $saran->pengirim());
        $this->assertFalse($saran->anonim());
    }

    public function test_batas_kiriman_dan_kotak_tertutup_tanpa_periode_aktif(): void
    {
        $uji = Volt::test('saran');
        foreach (range(1, 5) as $i) {
            $uji->set('isi', 'Saran nomor '.$i.' untuk OSIM.')->call('kirim')->assertHasNoErrors();
        }
        $uji->set('isi', 'Saran keenam yang terlalu cepat.')->call('kirim')->assertHasErrors(['isi']);
        $this->assertSame(5, Suggestion::count());

        \Illuminate\Support\Facades\RateLimiter::clear('kirim-saran:127.0.0.1');
        $this->periode->update(['status' => 'arsip']);

        $this->get('/saran')->assertSee('Kotak saran sedang ditutup');
        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        app(LayananSaran::class)->kirim(['isi' => 'Saran saat tidak ada periode aktif.']);
    }
}
