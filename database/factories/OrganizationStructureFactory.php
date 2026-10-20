<?php

namespace Database\Factories;

use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrganizationStructure>
 */
class OrganizationStructureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'periode_id' => fn () => OsisPeriod::aktif()?->id ?? OsisPeriod::factory()->aktif(),
            'gambar' => 'struktur/'.$this->faker->uuid().'.png',
            'rasio' => '4:3',
        ];
    }
}
