<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Enums\PlatformSosmed;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SosmedTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_menampilkan_konten_per_tab_dengan_orientasi_dan_jumlah_dilihat(): void
    {
        SocialPost::factory()->create(['platform' => 'youtube', 'judul' => 'Shorts Classmeeting', 'tautan' => 'https://www.youtube.com/shorts/abc', 'orientasi' => 'potret']);
        $ig = SocialPost::factory()->create(['platform' => 'instagram', 'judul' => 'Foto Pentas Seni', 'tautan' => 'https://www.instagram.com/p/xyz/', 'orientasi' => 'lanskap']);
        $ig->forceFill(['jumlah_klik' => 1234])->save();
        SocialPost::factory()->create(['judul' => 'Konten Tersembunyi', 'dipublikasikan' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dari Media Sosial OSIM')
            ->assertSeeInOrder(['YouTube', 'Instagram', 'TikTok'])
            ->assertDontSee('Shorts</', false)
            ->assertSee('Shorts Classmeeting')
            ->assertSee('1.234 kali dilihat')
            ->assertSee('calc(var(--tinggi) * 16 / 9)', false)
            ->assertSee(route('sosmed.buka', $ig))
            ->assertDontSee('Konten Tersembunyi');
    }

    public function test_sosmed_ada_di_bagian_bawah_beranda(): void
    {
        SocialPost::factory()->create();
        \App\Models\WorkProgram::factory()->create(['unggulan' => true, 'nama' => 'Program Contoh']);

        $this->get('/')->assertSeeInOrder(['Sorotan Program Kerja', 'Dari Media Sosial OSIM']);
    }

    public function test_youtube_mendukung_konten_lanskap(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        Volt::test('kelola.sosmed')
            ->set('platform', 'youtube')
            ->assertSet('orientasi', 'lanskap')
            ->set('judul', 'Video Upacara Lengkap')
            ->set('tautan', 'https://www.youtube.com/watch?v=abc123')
            ->set('thumbnail', UploadedFile::fake()->image('t.jpg', 1280, 720))
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertSame('lanskap', SocialPost::firstOrFail()->orientasi);
    }

    public function test_bagian_sosmed_disembunyikan_bila_kosong(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Dari Media Sosial OSIM');
    }

    public function test_buka_konten_menghitung_sekali_per_sesi_lalu_mengarahkan(): void
    {
        $post = SocialPost::factory()->create(['tautan' => 'https://www.tiktok.com/@osim/video/123']);

        $this->get(route('sosmed.buka', $post))->assertRedirect('https://www.tiktok.com/@osim/video/123');
        $this->get(route('sosmed.buka', $post))->assertRedirect();

        $this->assertSame(1, $post->fresh()->jumlah_klik);
    }

    public function test_tautan_di_luar_domain_platform_tidak_dibuka(): void
    {
        $post = SocialPost::factory()->create(['platform' => 'youtube', 'tautan' => 'https://situs-jahat.example/youtube.com']);

        $this->get(route('sosmed.buka', $post))->assertNotFound();
        $this->assertFalse(PlatformSosmed::TikTok->cocokDengan('http://www.tiktok.com/a'));
        $this->assertTrue(PlatformSosmed::YouTube->cocokDengan('https://youtu.be/abc'));
        $this->assertFalse(PlatformSosmed::Instagram->cocokDengan('https://instagram.com.palsu.id/p/1'));
    }

    public function test_hanya_pengurus_atau_admin_yang_bisa_mengelola(): void
    {
        $this->get('/kelola/sosmed')->assertRedirect('/login');

        $this->actingAs(User::factory()->create());
        $this->get('/kelola/sosmed')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/kelola/sosmed')->assertOk()->assertSee('Kelola Media Sosial');
    }

    public function test_pengurus_mengunggah_konten_dengan_thumbnail(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        Volt::test('kelola.sosmed')
            ->set('platform', 'instagram')
            ->assertSet('orientasi', 'lanskap')
            ->set('judul', 'Dokumentasi Upacara')
            ->set('tautan', 'https://www.tiktok.com/@salah/video/1')
            ->set('thumbnail', UploadedFile::fake()->image('thumb.jpg', 1280, 720))
            ->call('simpan')
            ->assertHasErrors('tautan')
            ->set('tautan', 'https://www.instagram.com/p/upacara/')
            ->call('simpan')
            ->assertHasNoErrors();

        $post = SocialPost::firstOrFail();
        $this->assertSame(PlatformSosmed::Instagram, $post->platform);
        $this->assertSame('lanskap', $post->orientasi);
        Storage::disk('public')->assertExists($post->thumbnail);
    }
}
