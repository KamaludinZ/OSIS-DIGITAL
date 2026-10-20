<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\StatusPeriode;
use App\Filament\Pages\KelolaPeriode;
use App\Models\Member;
use App\Models\OsisPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $lama;

    private OsisPeriod $aktif;

    protected function setUp(): void
    {
        parent::setUp();

        OsisPeriod::factory()->tahun(2024)->arsip()->create();
        // Anggota tercatat saat periode 2025/2026 masih aktif, lalu periode itu diarsipkan.
        $this->lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        Member::factory()->count(3)->create(['periode' => '2025/2026', 'periode_id' => $this->lama->id]);
        $this->lama->update(['status' => 'arsip']);
        $this->aktif = OsisPeriod::factory()->tahun(2026)->aktif()->create();
    }

    private function masukSebagai(Peran $peran = Peran::Pengurus): User
    {
        $this->actingAs($pengguna = User::factory()->create(['role' => $peran]));

        return $pengguna;
    }

    public function test_hanya_pengurus_dan_admin_yang_membuka_kelola_periode(): void
    {
        $this->get('/admin/periode-osim')->assertRedirect('/login');

        $this->masukSebagai(Peran::Panitia);
        $this->get('/admin/periode-osim')->assertForbidden();

        foreach ([Peran::Pengurus, Peran::Admin] as $peran) {
            $this->masukSebagai($peran);
            $this->get('/admin/periode-osim')->assertOk();
        }
    }

    public function test_halaman_utama_menampilkan_periode_aktif_dan_arsip(): void
    {
        $this->masukSebagai();

        $this->get('/admin/periode-osim')
            ->assertOk()
            ->assertSee('Periode OSIM')
            ->assertSeeInOrder(['Periode aktif', '2026/2027'])
            ->assertSee('2025/2026')
            ->assertSee('Arsip');
    }

    public function test_tambah_periode_baru_berstatus_disiapkan(): void
    {
        $this->masukSebagai();

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2027/2028', 'mulai' => '2027-07-12', 'selesai' => '2028-06-23'])
            ->assertHasNoFormErrors()
            ->assertNotified('Periode 2027/2028 disiapkan');

        $baru = OsisPeriod::where('nama', '2027/2028')->first();
        $this->assertSame(StatusPeriode::Disiapkan, $baru->status);
        $this->assertSame('2027-07-12', $baru->mulai->toDateString());
        $this->assertFalse($baru->punyaData());
    }

    public function test_form_periode_menolak_isian_tidak_valid(): void
    {
        $this->masukSebagai();

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2027-2028', 'mulai' => '2027-07-12', 'selesai' => '2027-07-01'])
            ->assertHasFormErrors(['nama' => 'regex', 'selesai' => 'after']);

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2025/2026', 'mulai' => '2025-07-12', 'selesai' => '2026-07-01'])
            ->assertHasFormErrors(['nama' => 'unique']);
    }

    public function test_ubah_nama_dan_rentang_periode(): void
    {
        $this->masukSebagai();

        Livewire::test(KelolaPeriode::class)
            ->callTableAction('ubah', $this->aktif, data: ['nama' => '2026/2027', 'mulai' => '2026-07-20', 'selesai' => '2027-06-30'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Periode 2026/2027 diperbarui');

        $this->assertSame('2026-07-20', $this->aktif->fresh()->mulai->toDateString());
        $this->assertSame(StatusPeriode::Aktif, $this->aktif->fresh()->status);
    }

    public function test_daftar_menampilkan_label_status_dengan_periode_aktif_di_atas(): void
    {
        $this->masukSebagai();
        $disiapkan = OsisPeriod::factory()->tahun(2027)->create();

        Livewire::test(KelolaPeriode::class)
            ->assertCanSeeTableRecords([$this->aktif, $disiapkan, $this->lama], inOrder: true)
            ->assertTableColumnFormattedStateSet('status', 'Aktif', $this->aktif)
            ->assertTableColumnFormattedStateSet('status', 'Disiapkan', $disiapkan)
            ->assertTableColumnFormattedStateSet('status', 'Arsip', $this->lama)
            ->assertTableColumnStateSet('members_count', 3, $this->lama)
            ->assertSee('Periode berjalan; data baru masuk ke sini.');
    }

    public function test_nama_periode_aktif_tampil_di_sidebar_panel(): void
    {
        $this->masukSebagai();
        $this->get('/admin/ringkasan')->assertOk()
            ->assertSeeInOrder(['osim-periode-aktif', 'Periode aktif', '2026/2027'])
            ->assertSee('/admin/periode-osim');

        // Panitia melihat nama periode tanpa tautan ke halaman kelola.
        $this->masukSebagai(Peran::Panitia);
        $this->get('/admin/ringkasan')->assertOk()
            ->assertSeeInOrder(['Periode aktif', '2026/2027'])
            ->assertDontSee('aria-label="Kelola periode OSIM"', false);
    }

    public function test_arsipkan_periode_lewat_dialog_konfirmasi(): void
    {
        $this->masukSebagai();

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('arsipkan', $this->lama)
            ->mountTableAction('arsipkan', $this->aktif)
            ->assertMountedActionModalSee(['Arsipkan periode 2026/2027?', 'Ruang OSIM tidak punya periode aktif', 'Ya, arsipkan'])
            ->callMountedTableAction()
            ->assertNotified('Periode 2026/2027 diarsipkan');

        $this->assertTrue($this->aktif->fresh()->adalahArsip());
        $this->assertNull(OsisPeriod::aktif());
        $this->get('/admin/periode-osim')->assertSee('Belum ada periode aktif');
    }

    public function test_periode_arsip_hanya_bisa_dilihat(): void
    {
        $this->masukSebagai();

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('ubah', $this->lama)
            ->assertTableActionHidden('arsipkan', $this->lama)
            ->assertTableActionVisible('lihat', $this->lama)
            ->assertTableActionHidden('lihat', $this->aktif)
            ->mountTableAction('lihat', $this->lama)
            ->assertMountedActionModalSee(['Arsip periode 2025/2026', 'hanya bisa dilihat']);

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        app(\App\Services\LayananPeriode::class)->ubah($this->lama, ['nama' => '2025/2026', 'mulai' => '2025-07-01', 'selesai' => '2026-06-30']);
    }

    public function test_hapus_hanya_periode_disiapkan_tanpa_data(): void
    {
        $this->masukSebagai();
        $kosong = OsisPeriod::factory()->tahun(2027)->create();

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('hapus', $this->aktif)
            ->assertTableActionHidden('hapus', $this->lama)
            ->callTableAction('hapus', $kosong)
            ->assertNotified('Periode 2027/2028 dihapus');

        $this->assertModelMissing($kosong);
    }

    public function test_penanda_status_aktif_pengurus_tampil(): void
    {
        $this->masukSebagai();
        User::factory()->pengurusMenjabat()->create(['name' => 'Ahmad Fauzan']);
        User::factory()->create(['role' => Peran::Pengurus, 'name' => 'Zaki Mantan']);

        $this->get('/admin/periode-osim')->assertOk()
            ->assertSee('Status pengurus')
            ->assertSee('1 dari 3 akun pengurus masih menjabat')
            ->assertSeeInOrder(['Ahmad Fauzan', 'Menjabat', 'Zaki Mantan', 'Tidak menjabat']);
    }

    public function test_validasi_rentang_tanggal_periode(): void
    {
        $this->masukSebagai();
        $tambah = fn (array $isian) => Livewire::test(KelolaPeriode::class)->callAction('tambah', data: $isian);

        // Tahun kedua harus tahun pertama + 1.
        $tambah(['nama' => '2027/2029', 'mulai' => '2027-07-12', 'selesai' => '2028-06-23'])
            ->assertHasFormErrors(['nama']);
        // Mulai harus di tahun pertama; selesai paling lambat akhir tahun kedua.
        $tambah(['nama' => '2027/2028', 'mulai' => '2028-01-05', 'selesai' => '2028-06-23'])
            ->assertHasFormErrors(['mulai']);
        $tambah(['nama' => '2027/2028', 'mulai' => '2027-07-12', 'selesai' => '2029-01-10'])
            ->assertHasFormErrors(['selesai']);
        // Tidak boleh bertumpang tindih dengan periode 2026/2027 (14 Jul 2026 – 26 Jun 2027).
        $tambah(['nama' => '2027/2028', 'mulai' => '2027-06-01', 'selesai' => '2028-05-31'])
            ->assertHasFormErrors(['mulai']);
        $tambah(['nama' => '2027/2028', 'mulai' => '2027-06-27', 'selesai' => '2028-06-23'])
            ->assertHasNoFormErrors();

        // Mengubah periode tidak bentrok dengan dirinya sendiri.
        Livewire::test(KelolaPeriode::class)
            ->callTableAction('ubah', $this->aktif, data: ['nama' => '2026/2027', 'mulai' => '2026-07-01', 'selesai' => '2027-06-26'])
            ->assertHasNoTableActionErrors();
    }

    public function test_layanan_menolak_rentang_tidak_valid_tanpa_lewat_form(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(\App\Services\LayananPeriode::class)->buat(['nama' => '2026/2027', 'mulai' => '2026-08-01', 'selesai' => '2027-06-01']);
    }

    public function test_mengaktifkan_periode_mengarsipkan_periode_aktif_lama(): void
    {
        $this->masukSebagai();
        $baru = OsisPeriod::factory()->tahun(2027)->create();

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('aktifkan', $this->aktif)
            ->assertTableActionHidden('aktifkan', $this->lama)
            ->mountTableAction('aktifkan', $baru)
            ->assertMountedActionModalSee(['Aktifkan periode 2027/2028?', 'Periode 2026/2027 yang sedang aktif otomatis diarsipkan'])
            ->callMountedTableAction()
            ->assertNotified('Periode 2027/2028 sekarang aktif');

        $this->assertTrue(OsisPeriod::aktif()->is($baru));
        $this->assertTrue($this->aktif->fresh()->adalahArsip());
        $this->assertSame(1, OsisPeriod::status(StatusPeriode::Aktif)->count());
    }

    public function test_arsip_dan_periode_aktif_tidak_bisa_diaktifkan_ulang(): void
    {
        $layanan = app(\App\Services\LayananPeriode::class);

        foreach ([$this->lama, $this->aktif] as $periode) {
            try {
                $layanan->aktifkan($periode);
                $this->fail('Seharusnya ditolak: '.$periode->nama);
            } catch (\App\Exceptions\PeriodeDitolak) {
            }
        }

        $this->assertTrue(OsisPeriod::aktif()->is($this->aktif));
    }

    public function test_aktifkan_saat_belum_ada_periode_aktif(): void
    {
        $this->aktif->update(['status' => StatusPeriode::Arsip]);
        $baru = OsisPeriod::factory()->tahun(2027)->create();

        $this->assertNull(app(\App\Services\LayananPeriode::class)->aktifkan($baru));
        $this->assertTrue(OsisPeriod::aktif()->is($baru));
    }

    public function test_arsip_ditolak_saat_pemilihan_masih_berjalan(): void
    {
        $this->masukSebagai();
        \App\Models\Election::factory()->create(['judul' => 'Pemilihan Ketua OSIM', 'status' => 'berjalan', 'periode_id' => $this->aktif->id]);

        Livewire::test(KelolaPeriode::class)
            ->callTableAction('arsipkan', $this->aktif)
            ->assertNotified('Pemilihan "Pemilihan Ketua OSIM" masih berjalan. Tutup pemilihan dulu sebelum mengarsipkan periode.');

        $this->assertTrue($this->aktif->fresh()->adalahAktif());

        $this->expectException(\App\Exceptions\PeriodeDitolak::class);
        app(\App\Services\LayananPeriode::class)->arsipkan($this->lama);
    }
}
