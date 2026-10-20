<?php

namespace Database\Factories;

use App\Enums\PlatformSosmed;
use App\Models\OsisPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialLink>
 */
class SocialLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'periode_id' => fn () => OsisPeriod::aktif()?->id ?? OsisPeriod::factory()->aktif(),
            'platform' => PlatformSosmed::Instagram,
            'nama_akun' => '@osim.mtsn2malang',
            'tautan' => 'https://www.instagram.com/osim.mtsn2malang',
        ];
    }

    public function platform(PlatformSosmed $platform): static
    {
        return $this->state(fn () => [
            'platform' => $platform,
            'tautan' => 'https://www.'.$platform->domain()[0].'/@osim.mtsn2malang',
        ]);
    }
}
