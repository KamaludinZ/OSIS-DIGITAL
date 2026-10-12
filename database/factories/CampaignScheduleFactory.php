<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignSchedule>
 */
class CampaignScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'tanggal' => today()->addDays(fake()->numberBetween(1, 7)),
            'waktu' => '10:00',
            'tempat' => 'Aula Utama',
        ];
    }
}
