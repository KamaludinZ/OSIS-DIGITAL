<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Pages\KotakSaran;
use App\Models\OsisPeriod;
use App\Models\Suggestion;
use App\Models\User;
use App\Services\LayananSaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KotakSaranTest extends TestCase
{
    use RefreshDatabase;

    private OsisPeriod $lama;

    private OsisPeriod $aktif;

    private Suggestion $arsip;

    private Suggestion $anonim;

    private Suggestion $bernama;

    private Suggestion $ditanggapi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lama = OsisPeriod::factory()->tahun(2025)->aktif()->create();
        $this->arsip = Suggestion::create(['isi' => 'Jadwal piket kelas sebaiknya ditempel di mading.']);
        app(LayananSaran::class)->tanggapi($this->arsip, 'Terima kasih, sudah ditempel.', User::factory()->pengurusMenjabat()->create(['name' => 'Pengurus Lama']));

        $this->aktif = OsisPeriod::factory()->tahun(2026)->create();
        app(\App\Services\LayananPeriode::class)->aktifkan($this->aktif);

        $this->ditanggapi = Suggestion::create(['isi' => 'Wifi perpustakaan sering mati.']);
        app(LayananSaran::class)->tanggapi($this->ditanggapi, 'Sudah disampaikan ke sarpras.', User::factory()->pengurusMenjabat()->create(['name' => 'Siti Nurhaliza']));
        $this->bernama = Suggestion::create(['isi' => 'Kajian Jumat diselingi lomba hafalan.', 'nama' => 'Nayla Safitri', 'kelas' => 'VIII-B']);
        $this->anonim = Suggestion::create(['isi' => 'Tambah tempat sampah terpilah di kantin.']);
    }

    public function test_halaman_publik_kotak_saran(): void
    {
        $this->get('/saran')->assertOk()
            ->assertSee('Kotak Saran')
            ->assertSee('Tulis saranmu')
            ->assertSee('Bagaimana saran diproses?');
    }

    public function test_daftar_saran_di_panel_admin(): void
    {
        $this->get('/admin/kotak-saran')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/kotak-saran')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/kotak-saran')->assertOk()->assertSee('Kotak Saran');

        Livewire::test(KotakSaran::class)
            // Bawaan: periode aktif saja.
            ->assertCanSeeTableRecords([$this->anonim, $this->bernama, $this->ditanggapi], inOrder: true)
            ->assertCanNotSeeTableRecords([$this->arsip])
            ->assertTableColumnStateSet('nama', 'Anonim', $this->anonim)
            ->assertTableColumnStateSet('nama', 'Nayla Safitri · VIII-B', $this->bernama)
            ->assertTableColumnStateSet('status', 'Ditanggapi', $this->ditanggapi)
            ->filterTable('status', 'belum')
            ->assertCanSeeTableRecords([$this->anonim, $this->bernama])
            ->assertCanNotSeeTableRecords([$this->ditanggapi])
            ->filterTable('periode', (string) $this->lama->id)
            ->filterTable('status', null)
            ->assertCanSeeTableRecords([$this->arsip]);

        $this->assertSame('2', KotakSaran::getNavigationBadge());
    }

    public function test_form_tanggapan_hanya_untuk_pengurus_menjabat(): void
    {
        // Pengurus tidak menjabat: tidak ada tombol tanggapi.
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        Livewire::test(KotakSaran::class)->assertTableActionHidden('tanggapi', $this->anonim);

        $this->actingAs($pengurus = User::factory()->pengurusMenjabat()->create(['name' => 'Ahmad Fauzan']));
        Livewire::test(KotakSaran::class)
            ->assertTableActionHasLabel('tanggapi', 'Tanggapi', $this->anonim)
            ->assertTableActionHasLabel('tanggapi', 'Ubah tanggapan', $this->ditanggapi)
            ->callTableAction('tanggapi', $this->anonim, data: ['tanggapan' => 'ok'])
            ->assertHasTableActionErrors(['tanggapan' => 'min']);

        Livewire::test(KotakSaran::class)
            ->callTableAction('tanggapi', $this->anonim, data: ['tanggapan' => 'Siap, kami ajukan ke koperasi madrasah pekan ini.'])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Tanggapan terkirim')
            ->filterTable('periode', (string) $this->lama->id)
            ->assertTableActionHidden('tanggapi', $this->arsip);

        $this->anonim->refresh();
        $this->assertTrue($this->anonim->penanggap->is($pengurus));
        $this->assertStringStartsWith('Siap', $this->anonim->tanggapan);
        $this->assertNotNull($this->anonim->ditanggapi_pada);
    }

    public function test_layanan_menolak_tanggapan_tanpa_hak_atau_untuk_arsip(): void
    {
        $layanan = app(LayananSaran::class);

        foreach ([User::factory()->create(['role' => Peran::Pengurus]), User::factory()->create(['role' => Peran::Admin])] as $tanpaHak) {
            try {
                $layanan->tanggapi($this->anonim, 'Tanggapan tanpa hak.', $tanpaHak);
                $this->fail('Seharusnya ditolak.');
            } catch (\Illuminate\Auth\Access\AuthorizationException) {
            }
        }

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $layanan->tanggapi(Suggestion::diPeriode($this->lama)->first(), 'Menanggapi arsip.', User::factory()->pengurusMenjabat()->create());
    }

    public function test_riwayat_saran_per_periode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/admin/kotak-saran')->assertOk()
            ->assertSeeInOrder(['Periode aktif', '2026/2027', '3', 'saran', '1 ditanggapi', 'Arsip', '2025/2026', '1', 'saran', '1 ditanggapi']);

        Livewire::test(KotakSaran::class)
            ->call('lihatPeriode', $this->lama->id)
            ->assertCanSeeTableRecords([$this->arsip])
            ->assertCanNotSeeTableRecords([$this->anonim])
            ->mountTableAction('lihat', $this->arsip)
            ->assertMountedActionModalSee(['Jadwal piket kelas', 'Tanggapan Pengurus Lama', 'Periode 2025/2026']);

        Livewire::test(KotakSaran::class)
            ->mountTableAction('lihat', $this->anonim)
            ->assertMountedActionModalSee(['Anonim', 'Belum ditanggapi.']);
    }
}
