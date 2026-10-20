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

    public function test_saran_dibatasi_global_scope_periode(): void
    {
        $this->assertContains(\App\Models\Concerns\IkutPeriode::class, class_uses_recursive(Suggestion::class));

        $lama = Suggestion::factory()->create(['isi' => 'Saran periode lama.']);
        app(\App\Services\LayananPeriode::class)->aktifkan($baru = OsisPeriod::factory()->tahun(2027)->create());
        $sekarang = Suggestion::factory()->bernama()->create();

        $this->assertSame([$sekarang->id], Suggestion::pluck('id')->all());
        $this->assertSame($baru->id, $sekarang->periode_id);
        $this->assertNull(Suggestion::find($lama->id));
        $this->assertSame([$lama->id], Suggestion::diPeriode($this->periode)->pluck('id')->all());
        $this->assertSame(2, Suggestion::semuaPeriode()->count());
        $this->assertSame(1, $this->periode->suggestions()->count());

        // Saran periode arsip tidak bisa diubah/dihapus.
        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        Suggestion::diPeriode($this->periode)->first()->delete();
    }

    public function test_pesan_validasi_dan_format_tanggal_indonesia(): void
    {
        app()->setLocale('id');

        Volt::test('saran')
            ->call('kirim')->assertSee('Tulis dulu saranmu.')
            ->set('isi', 'pendek')->call('kirim')->assertSee('Saran terlalu pendek. Tulis minimal 10 karakter')
            ->set('isi', 'Saran yang cukup panjang untuk OSIM.')->set('bernama', true)->call('kirim')
            ->assertSee('Tulis namamu, atau pilih kirim sebagai anonim.');

        $waktu = \Illuminate\Support\Carbon::parse('2026-10-19 11:40', 'Asia/Jakarta');
        $this->assertSame('Senin, 19 Oktober 2026 pukul 11.40 WIB', \App\Support\Tanggal::lengkap($waktu));
        $this->assertSame('19 Okt 2026, 11.40', \App\Support\Tanggal::singkat($waktu));
        $this->travelTo($waktu->copy()->addHours(3));
        $this->assertSame('3 jam yang lalu', \App\Support\Tanggal::relatif($waktu));
        $this->assertSame('—', \App\Support\Tanggal::lengkap(null));

        $this->assertSame('Tanggal mulai harus berupa tanggal yang benar.', validator(['mulai' => 'x'], ['mulai' => 'date'])->errors()->first('mulai'));
    }
}
