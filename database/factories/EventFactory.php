<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $mulai = now()->addDays(fake()->numberBetween(1, 30))->startOfDay();

        return [
            'nama' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(15),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $mulai,
            'tempat' => 'Aula Utama',
        ];
    }
}
