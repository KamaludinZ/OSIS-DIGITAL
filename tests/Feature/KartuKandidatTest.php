<?php

namespace Tests\Feature;

use Tests\TestCase;

class KartuKandidatTest extends TestCase
{
    private array $calon = [
        'id' => 1, 'nomor_urut' => 2, 'berpasangan' => true, 'foto_url' => null, 'slogan' => 'Bersama bergerak',
        'nama_ketua' => 'Nadia Kusuma', 'kelas_ketua' => 'VIII-A', 'nama_wakil' => 'Rafi Akbar', 'kelas_wakil' => 'VII-C',
    ];

    public function test_kartu_menampilkan_identitas_paslon(): void
    {
        $this->blade('<x-kartu-kandidat :calon="$calon" />', ['calon' => $this->calon])
            ->assertSeeInOrder(['Kartu Pasangan Calon', 'Nomor urut 2', 'Calon Ketua', 'Nadia Kusuma', 'Calon Wakil', 'Rafi Akbar', 'Bersama bergerak'])
            ->assertSee('data-terpilih="tidak"', false)
            ->assertDontSee('Pilihanmu');
    }

    public function test_penanda_terpilih(): void
    {
        $this->blade('<x-kartu-kandidat :calon="$calon" :terpilih="true" />', ['calon' => $this->calon])
            ->assertSee('data-terpilih="ya"', false)
            ->assertSee('Pilihanmu')
            ->assertSee('ring-hijau-600', false);
    }

    public function test_format_ketua_saja(): void
    {
        $this->blade('<x-kartu-kandidat :calon="$calon" />', ['calon' => [...$this->calon, 'berpasangan' => false, 'nama_wakil' => null]])
            ->assertSee('Kartu Calon Ketua')
            ->assertDontSee('Calon Wakil');
    }
}
