<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\Announcement;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_bisa_dijalankan_berulang_dan_akun_contoh_bisa_masuk(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(5, User::count());
        $this->assertSame(3, Candidate::count());
        $this->assertSame(5, Announcement::count());

        $siswa = User::where('email', 'siswa@osis.test')->first();
        $this->assertSame(Peran::Siswa, $siswa->role);
        $this->assertTrue(Hash::check('password', $siswa->password));
        $this->assertSame(Peran::Panitia, User::where('email', 'panitia@osis.test')->first()->role);
    }
}
