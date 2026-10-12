<?php

namespace Database\Factories;

use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidate>
 */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'nomor_urut' => fake()->unique()->numberBetween(1, 99),
            'nama_ketua' => fake()->name(),
            'kelas_ketua' => 'VIII-'.fake()->randomElement(['A', 'B', 'C', 'D']),
            'nama_wakil' => fake()->name(),
            'kelas_wakil' => 'VII-'.fake()->randomElement(['A', 'B', 'C', 'D']),
            'slogan' => fake()->sentence(5),
            'latar_belakang' => fake()->paragraph(),
            'visi' => fake()->sentence(12),
            'misi' => [fake()->sentence(), fake()->sentence(), fake()->sentence()],
            'foto_url' => null,
        ];
    }
}
