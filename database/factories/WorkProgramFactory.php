<?php

namespace Database\Factories;

use App\Enums\StatusProgramKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkProgram>
 */
class WorkProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->sentence(3),
            'deskripsi' => fake()->sentence(12),
            'unggulan' => false,
            'status' => StatusProgramKerja::Rencana,
            'capaian' => null,
        ];
    }
}
