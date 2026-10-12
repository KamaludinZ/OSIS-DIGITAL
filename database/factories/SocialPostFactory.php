<?php

namespace Database\Factories;

use App\Enums\PlatformSosmed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialPost>
 */
class SocialPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'platform' => PlatformSosmed::TikTok,
            'judul' => fake()->sentence(5),
            'tautan' => 'https://www.tiktok.com/@osim.mtsn2malang/video/'.fake()->numerify('7##########'),
            'thumbnail' => 'sosmed/contoh.png',
            'orientasi' => 'potret',
            'dipublikasikan' => true,
        ];
    }
}
