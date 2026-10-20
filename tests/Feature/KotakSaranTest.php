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
}
