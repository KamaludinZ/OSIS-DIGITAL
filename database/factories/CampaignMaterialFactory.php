<?php

namespace Database\Factories;

use App\Enums\TipeMateri;
use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignMaterial>
 */
class CampaignMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'tipe' => TipeMateri::Janji,
            'url_konten' => null,
            'deskripsi' => fake()->sentence(),
            'urutan' => 0,
        ];
    }
}
