<?php

namespace Database\Factories;

use App\Enums\StatusPemilihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Election>
 */
class ElectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judul' => 'Pemilihan Ketua OSIM '.now()->year.'/'.(now()->year + 1),
            'mulai' => now()->subHour(),
            'selesai' => now()->addHours(6),
            'status' => StatusPemilihan::Berjalan,
            'hasil_publik' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => StatusPemilihan::Draft,
            'mulai' => now()->addDay(),
            'selesai' => now()->addDay()->addHours(8),
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn () => [
            'status' => StatusPemilihan::Selesai,
            'mulai' => now()->subDay(),
            'selesai' => now()->subHours(2),
        ]);
    }
}
