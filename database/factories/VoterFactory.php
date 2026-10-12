<?php

namespace Database\Factories;

use App\Enums\JenisPemilih;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Voter>
 */
class VoterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jenis' => JenisPemilih::Siswa,
            'nomor_induk' => '00'.fake()->unique()->numerify('########'),
            'nama' => fake()->name(),
            'kelas' => fake()->randomElement(['VII', 'VIII', 'IX']).'-'.fake()->randomElement(['A', 'B', 'C']),
            'jabatan' => null,
            'tanggal_lahir' => fake()->dateTimeBetween('-15 years', '-12 years')->format('Y-m-d'),
            'verifikasi' => 'terverifikasi',
        ];
    }

    public function gtk(): static
    {
        return $this->state(fn () => [
            'jenis' => JenisPemilih::Gtk,
            'nomor_induk' => fake()->unique()->numerify('3573############'),
            'kelas' => null,
            'jabatan' => 'Guru',
            'tanggal_lahir' => fake()->dateTimeBetween('-55 years', '-25 years')->format('Y-m-d'),
        ]);
    }

    /** Pemilih dengan kode pilih yang sudah dibuat. */
    public function berkode(?string $kode = null): static
    {
        return $this->afterCreating(function ($voter) use ($kode) {
            $voter->forceFill(['kode_pilih' => $kode ?? \App\Models\Voter::kodeBaru(), 'kode_dibuat_pada' => now()])->save();
        });
    }
}
