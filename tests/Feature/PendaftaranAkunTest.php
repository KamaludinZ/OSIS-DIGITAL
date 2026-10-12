<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PendaftaranAkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_membuat_akun_staf_dan_bisa_langsung_masuk(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Bu Rahma', 'email' => 'Rahma@MTSN2.test', 'role' => 'panitia', 'password' => 'kuatsekali8'])
            ->call('create')->assertHasNoFormErrors();

        $akun = User::firstWhere('email', 'rahma@mtsn2.test');
        $this->assertSame(Peran::Panitia, $akun->role);
        $this->assertTrue($akun->aktif);
        $this->assertNull($akun->candidate_id);
        $this->assertTrue(Hash::check('kuatsekali8', $akun->password));
    }

    public function test_validasi_pendaftaran(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        User::factory()->create(['email' => 'ada@osim.test']);
        $calon = Candidate::factory()->create();
        User::factory()->create(['role' => Peran::Kandidat, 'candidate_id' => $calon->id]);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => 'ada@osim.test', 'role' => 'admin', 'password' => 'pendek'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'unique', 'password']);

        // Akun kandidat wajib terhubung, dan satu kandidat satu akun.
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Calon', 'email' => 'calon@osim.test', 'role' => 'kandidat', 'password' => 'kuatsekali8'])
            ->call('create')->assertHasFormErrors(['candidate_id' => 'required']);
        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Calon', 'email' => 'calon@osim.test', 'role' => 'kandidat', 'candidate_id' => $calon->id, 'password' => 'kuatsekali8'])
            ->call('create')->assertHasFormErrors(['candidate_id' => 'unique']);
    }

    public function test_hanya_admin_dan_tidak_bisa_hapus_diri_sendiri(): void
    {
        $admin = User::factory()->create(['role' => Peran::Admin]);
        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);

        $this->assertFalse($pengurus->can('create', User::class));
        $this->assertFalse($pengurus->can('update', $admin));
        $this->assertTrue($admin->can('delete', $pengurus));
        $this->assertFalse($admin->can('delete', $admin));

        $this->actingAs($pengurus);
        Livewire::test(CreateUser::class)->assertForbidden();
        $this->get('/admin/akun')->assertForbidden();

        // Reset kata sandi oleh admin; kata sandi kosong tidak mengubah yang lama.
        $this->actingAs($admin);
        $lama = $pengurus->password;
        Livewire::test(EditUser::class, ['record' => $pengurus->id])->fillForm(['password' => ''])->call('save')->assertHasNoFormErrors();
        $this->assertSame($lama, $pengurus->fresh()->password);
        Livewire::test(EditUser::class, ['record' => $pengurus->id])->fillForm(['password' => 'barusaja12'])->call('save')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('barusaja12', $pengurus->fresh()->password));
    }
}
