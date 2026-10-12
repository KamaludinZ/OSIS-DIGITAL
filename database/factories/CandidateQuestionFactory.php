<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CandidateQuestion>
 */
class CandidateQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'penanya_id' => User::factory(),
            'pertanyaan' => fake()->sentence(10).'?',
            'jawaban' => null,
        ];
    }

    public function dijawab(): static
    {
        return $this->state(fn () => ['jawaban' => fake()->paragraph(), 'dijawab_pada' => now()]);
    }
}
