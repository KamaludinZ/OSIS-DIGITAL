<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\KelolaPeriode;
use App\Models\User;
use App\Support\DataTiruanPeriode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaPeriodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pengurus_dan_admin_yang_membuka_kelola_periode(): void
    {
        $this->get('/admin/periode-osim')->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/periode-osim')->assertForbidden();

        foreach ([Peran::Pengurus, Peran::Admin] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]));
            $this->get('/admin/periode-osim')->assertOk();
        }
    }

    public function test_halaman_utama_menampilkan_periode_aktif_dan_arsip(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/admin/periode-osim')
            ->assertOk()
            ->assertSee('Periode OSIM')
            ->assertSeeInOrder(['Periode aktif', '2026/2027'])
            ->assertSee('2025/2026')
            ->assertSee('Arsip');
    }

    public function test_tambah_periode_baru_berstatus_disiapkan(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2027/2028', 'mulai' => '2027-07-12', 'selesai' => '2028-06-23'])
            ->assertHasNoFormErrors()
            ->assertNotified('Periode 2027/2028 disiapkan');

        $baru = collect(DataTiruanPeriode::semua())->firstWhere('nama', '2027/2028');
        $this->assertSame('disiapkan', $baru['status']);
        $this->assertSame(0, $baru['pengurus']);
    }

    public function test_form_periode_menolak_isian_tidak_valid(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2027-2028', 'mulai' => '2027-07-12', 'selesai' => '2027-07-01'])
            ->assertHasFormErrors(['nama' => 'regex', 'selesai' => 'after']);

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2025/2026', 'mulai' => '2025-07-12', 'selesai' => '2026-07-01'])
            ->assertHasFormErrors(['nama']);
    }

    public function test_ubah_nama_dan_rentang_periode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->callTableAction('ubah', 3, data: ['nama' => '2026/2027', 'mulai' => '2026-07-20', 'selesai' => '2027-06-30'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Periode 2026/2027 diperbarui');

        $this->assertSame('2026-07-20', DataTiruanPeriode::semua()[3]['mulai']);
        $this->assertSame('aktif', DataTiruanPeriode::semua()[3]['status']);
    }

    public function test_daftar_menampilkan_label_status_dengan_periode_aktif_di_atas(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->callAction('tambah', data: ['nama' => '2027/2028', 'mulai' => '2027-07-12', 'selesai' => '2028-06-23'])
            ->assertCanSeeTableRecords([3, 4, 2, 1], inOrder: true)
            ->assertTableColumnFormattedStateSet('status', 'Aktif', 3)
            ->assertTableColumnFormattedStateSet('status', 'Disiapkan', 4)
            ->assertTableColumnFormattedStateSet('status', 'Arsip', 1)
            ->assertSee('Periode berjalan; data baru masuk ke sini.');
    }

    public function test_nama_periode_aktif_tampil_di_sidebar_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/ringkasan')->assertOk()
            ->assertSeeInOrder(['osim-periode-aktif', 'Periode aktif', '2026/2027'])
            ->assertSee('/admin/periode-osim');

        // Panitia melihat nama periode tanpa tautan ke halaman kelola.
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/ringkasan')->assertOk()
            ->assertSeeInOrder(['Periode aktif', '2026/2027'])
            ->assertDontSee('aria-label="Kelola periode OSIM"', false);
    }

    public function test_arsipkan_periode_lewat_dialog_konfirmasi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('arsipkan', 2)
            ->mountTableAction('arsipkan', 3)
            ->assertMountedActionModalSee(['Arsipkan periode 2026/2027?', 'Ruang OSIM tidak punya periode aktif', 'Ya, arsipkan'])
            ->callMountedTableAction()
            ->assertNotified('Periode 2026/2027 diarsipkan');

        $this->assertSame('arsip', DataTiruanPeriode::semua()[3]['status']);
        $this->assertNull(DataTiruanPeriode::aktif());
        $this->get('/admin/periode-osim')->assertSee('Belum ada periode aktif');
    }

    public function test_periode_arsip_hanya_bisa_dilihat(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Livewire::test(KelolaPeriode::class)
            ->assertTableActionHidden('ubah', 2)
            ->assertTableActionHidden('arsipkan', 2)
            ->assertTableActionVisible('lihat', 2)
            ->assertTableActionHidden('lihat', 3)
            ->mountTableAction('lihat', 2)
            ->assertMountedActionModalSee(['Arsip periode 2025/2026', 'hanya bisa dilihat', '41']);
    }

    public function test_penanda_status_aktif_pengurus_tampil(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/admin/periode-osim')->assertOk()
            ->assertSee('Status pengurus')
            ->assertSee('4 dari 6 akun pengurus masih menjabat')
            ->assertSeeInOrder(['Ahmad Fauzan', 'Menjabat', 'Dimas Arya', 'Tidak menjabat']);
    }
}
