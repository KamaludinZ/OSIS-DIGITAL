<?php

namespace Database\Factories;

use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Suggestion>
 */
class SuggestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'periode_id' => fn () => OsisPeriod::aktif()?->id ?? OsisPeriod::factory()->aktif(),
            'isi' => $this->faker->sentence(12),
        ];
    }

    public function bernama(string $nama = 'Nayla Safitri', string $kelas = 'VIII-B'): static
    {
        return $this->state(['nama' => $nama, 'kelas' => $kelas]);
    }
}
