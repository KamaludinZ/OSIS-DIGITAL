<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelolaPengumumanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengurus_menulis_draf_lalu_menayangkan(): void
    {
        $pengurus = User::factory()->create(['role' => Peran::Pengurus, 'name' => 'Rani']);
        $panitia = User::factory()->create(['role' => Peran::Panitia]);
        $this->actingAs($pengurus);
        $this->get('/admin/pengumuman')->assertOk()->assertSee('Media Sosial');

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['judul' => 'Lomba Kaligrafi', 'kategori' => 'Keagamaan', 'isi' => "Paragraf satu.\n\nParagraf dua.", 'dipublikasikan' => false])
            ->call('create')->assertHasNoFormErrors();

        $a = Announcement::firstWhere('judul', 'Lomba Kaligrafi');
        $this->assertFalse($a->dipublikasikan);
        $this->assertNull($a->dipublikasikan_pada);
        $this->assertSame($pengurus->id, $a->penulis_id);
        $this->get('/pengumuman/'.$a->id)->assertNotFound();

        Livewire::test(ListAnnouncements::class)->callTableAction('tayangkan', $a);
        $this->assertTrue($a->fresh()->sudahTayang());
        $this->get('/pengumuman/'.$a->id)->assertOk()->assertSee('Paragraf dua.');
        $this->assertSame(1, $panitia->notifications()->count()); // pemicu pemberitahuan ikut jalan
    }

    public function test_jadwal_tayang_dan_akses(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['judul' => 'Libur Maulid', 'kategori' => 'Umum', 'isi' => 'Sekolah libur.', 'dipublikasikan' => true, 'dipublikasikan_pada' => now()->addDay()->format('Y-m-d H:i')])
            ->call('create')->assertHasNoFormErrors();

        $a = Announcement::firstWhere('judul', 'Libur Maulid');
        $this->get('/pengumuman/'.$a->id)->assertNotFound();
        Livewire::test(ListAnnouncements::class)->filterTable('status', 'terjadwal')->assertCanSeeTableRecords([$a]);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['judul' => '', 'isi' => ''])->call('create')->assertHasFormErrors(['judul' => 'required', 'isi' => 'required']);

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/pengumuman')->assertForbidden();
    }
}
