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

    public function test_tambah_ubah_hapus_akun_dengan_validasi_ramah(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Volt::test('kelola.akun-sosmed')
            // Satu akun per platform per periode.
            ->set('platform', 'instagram')->set('namaAkun', '@baru')->set('tautan', 'https://www.instagram.com/baru')
            ->call('simpan')->assertHasErrors(['platform'])->assertSee('Akun Instagram periode ini sudah ada')
            // Domain harus sesuai platform.
            ->call('ubah', 2)->assertSet('platform', 'youtube')
            ->set('tautan', 'http://youtube.com/x')->call('simpan')->assertHasErrors(['tautan'])
            ->assertSee('Tautan harus diawali https:// dan berasal dari youtube.com atau youtu.be.')
            ->set('tautan', 'https://www.youtube.com/@osimbaru')->call('simpan')->assertHasNoErrors()
            ->assertSee('Akun diperbarui.')->assertSet('diubah', null)
            ->call('hapus', 3)->assertSee('Akun dihapus.')
            ->set('platform', 'tiktok')->set('namaAkun', '@osim.baru')->set('tautan', 'https://www.tiktok.com/@osim.baru')
            ->call('simpan')->assertHasNoErrors()->assertSee('Akun ditambahkan.');

        $akun = collect(\App\Support\DataTiruanAkunSosmed::semua())->where('periode', '2026/2027');
        $this->assertSame('https://www.youtube.com/@osimbaru', $akun->firstWhere('id', 2)['tautan']);
        $this->assertSame('@osim.baru', $akun->firstWhere('platform', 'tiktok')['nama_akun']);
        $this->assertCount(3, $akun);
    }

    public function test_akun_arsip_tidak_bisa_diubah(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Volt::test('kelola.akun-sosmed')
            ->set('periode', '2025/2026')
            ->assertDontSee('Tambah akun')
            ->call('hapus', 4)->assertForbidden();
    }

    public function test_tautan_periode_aktif_tampil_di_halaman_publik(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Akun media sosial resmi OSIM')
            ->assertSee('href="https://www.instagram.com/osim.mtsn2malang"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('@osim2526.mtsn2');

        $this->get('/saran')->assertOk()->assertSee('@osim.mtsn2malang');
    }
}
