<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AkunSosmedTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_kelola_menampilkan_akun_periode_aktif_dan_arsip(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/kelola/sosmed')->assertOk()
            ->assertSeeInOrder(['Akun resmi OSIM', '2026/2027', '3', '2025/2026', '1', '@osim.mtsn2malang', 'Konten baru']);

        Volt::test('kelola.akun-sosmed')
            ->assertSee('OSIM MTsN 2 Kota Malang')
            ->assertDontSee('@osim2526.mtsn2')
            ->set('periode', '2025/2026')
            ->assertSee('@osim2526.mtsn2')
            ->assertSee('hanya bisa dilihat');
    }

    public function test_hanya_pengelola_publikasi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        Volt::test('kelola.akun-sosmed')->assertForbidden();
    }

    public function test_periode_tidak_dikenal_kembali_ke_periode_aktif(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Volt::test('kelola.akun-sosmed')
            ->set('periode', '1999/2000')
            ->assertSet('periode', '2026/2027')
            ->assertDontSee('hanya bisa dilihat')
            ->set('periode', '2025/2026')
            ->assertSee('grayscale', false);
    }
}
