<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(5),
            'kategori' => 'Umum',
            'ringkasan' => fake()->sentence(14),
            'isi' => fake()->paragraphs(3, true),
            'penulis_label' => 'Pengurus OSIM',
            'dipublikasikan' => true,
            'dipublikasikan_pada' => now()->subDay(),
        ];
    }

    public function draf(): static
    {
        return $this->state(fn () => ['dipublikasikan' => false, 'dipublikasikan_pada' => null]);
    }
}
