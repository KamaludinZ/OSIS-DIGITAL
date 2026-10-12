<?php

namespace Tests\Feature\Settings;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_pengaturan_akun_tampil(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia, 'email' => 'panitia@osim.test']));

        $this->get('/settings/profile')->assertOk()
            ->assertSee('Pengaturan Akun')->assertSee('panitia@osim.test')->assertSee('Panitia pemilihan')
            ->assertSee('Ke Ruang OSIM')->assertDontSee('Delete account');
        $this->get('/settings/appearance')->assertRedirect('/settings/profile');
    }

    public function test_nama_bisa_diubah_tetapi_email_tidak(): void
    {
        $user = User::factory()->create(['role' => Peran::Pengurus]);
        $emailLama = $user->email;
        $this->actingAs($user);

        Volt::test('settings.profile')
            ->set('name', 'Nama Baru')
            ->call('updateProfileInformation')
            ->assertHasNoErrors()
            ->assertDispatched('profile-updated');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($emailLama, $user->email);

        Volt::test('settings.profile')->set('name', '')->call('updateProfileInformation')->assertHasErrors(['name' => 'required']);
    }

    public function test_akun_kandidat_diarahkan_ke_tanya_jawab_dari_pengaturan(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $this->actingAs(User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $calon->id]));

        $this->get('/settings/profile')->assertOk()->assertSee('Ke tanya jawab')->assertSee('No. '.$calon->nomor_urut)->assertDontSee('Ke Ruang OSIM');
    }

    public function test_baca_dan_simpan_preferensi_pemberitahuan_akun(): void
    {
        $calon = \App\Models\Candidate::factory()->create();
        $kandidat = User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $calon->id]);
        $this->actingAs($kandidat);

        $this->get('/settings/notifikasi')->assertOk()->assertSee('Pertanyaan untuk kandidat')->assertSee('Pengumuman baru');

        Volt::test('settings.notifikasi')
            ->assertSet('pilihan', ['pertanyaan' => true, 'pemilihan' => true, 'pengumuman' => true])
            ->set('pilihan.pengumuman', false)
            ->set('pilihan.lainnya', true) // jenis asing diabaikan
            ->call('simpan')
            ->assertDispatched('preferensi-tersimpan');

        $this->assertFalse($kandidat->fresh()->mauMenerima('pengumuman'));
        $this->assertTrue($kandidat->fresh()->mauMenerima('pertanyaan'));
        $this->assertSame(3, $kandidat->preferensiNotifikasi()->count());

        Volt::test('settings.notifikasi')->assertSet('pilihan.pengumuman', false);

        // Panitia tidak melihat jenis khusus kandidat.
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/settings/notifikasi')->assertOk()->assertDontSee('Pertanyaan untuk kandidat');
    }
}
