<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'kelas' => fake()->randomElement(['VIII', 'IX']).'-'.fake()->randomElement(['A', 'B', 'C']),
            'divisi_id' => null,
            'jabatan' => 'Anggota',
            'periode' => Member::periodeAktif(),
        ];
    }
}
