<?php

namespace Tests\Feature;

use App\Support\DataTiruanSaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class KotakSaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_kotak_saran(): void
    {
        $this->get('/saran')->assertOk()
            ->assertSee('Kotak Saran')
            ->assertSee('Tulis saranmu')
            ->assertSee('Bagaimana saran diproses?');
    }

    public function test_kirim_saran_dari_halaman_publik(): void
    {
        Volt::test('saran')
            ->set('isi', 'pendek')->call('kirim')->assertHasErrors(['isi' => 'min'])
            ->set('isi', 'Tolong tambah rak sepeda di dekat gerbang belakang.')->call('kirim')
            ->assertHasNoErrors()->assertSet('isi', '')->assertSee('Saran terkirim');

        $this->assertSame('Tolong tambah rak sepeda di dekat gerbang belakang.', DataTiruanSaran::semua()[0]['isi']);
        $this->assertNull(DataTiruanSaran::semua()[0]['nama']);
    }

    public function test_daftar_saran_di_panel_admin(): void
    {
        $this->get('/admin/kotak-saran')->assertRedirect('/login');
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Panitia]));
        $this->get('/admin/kotak-saran')->assertForbidden();

        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));
        $this->get('/admin/kotak-saran')->assertOk()->assertSee('Kotak Saran');

        \Livewire\Livewire::test(\App\Filament\Pages\KotakSaran::class)
            // Bawaan: periode aktif saja (saran 2025/2026 tidak tampil).
            ->assertCanSeeTableRecords([5, 4, 3, 2])
            ->assertCanNotSeeTableRecords([1])
            ->assertTableColumnStateSet('nama', 'Anonim', 5)
            ->assertTableColumnStateSet('nama', 'Nayla Safitri · VIII-B', 4)
            ->assertTableColumnStateSet('status', 'Ditanggapi', 3)
            ->filterTable('status', 'belum')
            ->assertCanSeeTableRecords([5, 4])
            ->assertCanNotSeeTableRecords([3, 2])
            ->filterTable('periode', '2025/2026')
            ->filterTable('status', null)
            ->assertCanSeeTableRecords([1]);

        $this->assertSame('2', \App\Filament\Pages\KotakSaran::getNavigationBadge());
    }

    public function test_form_tanggapan_hanya_untuk_pengurus_menjabat(): void
    {
        \App\Models\OsisPeriod::factory()->tahun(2026)->aktif()->create();

        // Pengurus tidak menjabat: tidak ada tombol tanggapi.
        $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Peran::Pengurus]));
        \Livewire\Livewire::test(\App\Filament\Pages\KotakSaran::class)->assertTableActionHidden('tanggapi', 5);

        $this->actingAs(\App\Models\User::factory()->pengurusMenjabat()->create(['name' => 'Ahmad Fauzan']));
        \Livewire\Livewire::test(\App\Filament\Pages\KotakSaran::class)
            ->assertTableActionHasLabel('tanggapi', 'Tanggapi', 5)
            ->assertTableActionHasLabel('tanggapi', 'Ubah tanggapan', 3)
            ->callTableAction('tanggapi', 5, data: ['tanggapan' => 'ok'])
            ->assertHasTableActionErrors(['tanggapan' => 'min']);

        \Livewire\Livewire::test(\App\Filament\Pages\KotakSaran::class)
            ->callTableAction('tanggapi', 5, data: ['tanggapan' => 'Siap, kami ajukan ke koperasi madrasah pekan ini.'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Tanggapan terkirim')
            ->filterTable('periode', '2025/2026')
            ->assertTableActionHidden('tanggapi', 1);

        $saran = collect(DataTiruanSaran::semua())->firstWhere('id', 5);
        $this->assertSame('Ahmad Fauzan', $saran['penanggap']);
        $this->assertStringStartsWith('Siap', $saran['tanggapan']);
    }
}
