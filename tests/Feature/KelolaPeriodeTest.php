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
            ->callTableAction('ubah', 2, data: ['nama' => '2025/2026', 'mulai' => '2025-07-21', 'selesai' => '2026-06-30'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Periode 2025/2026 diperbarui');

        $this->assertSame('2025-07-21', DataTiruanPeriode::semua()[2]['mulai']);
        $this->assertSame('arsip', DataTiruanPeriode::semua()[2]['status']);
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
}
