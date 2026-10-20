<?php

namespace Database\Factories;

use App\Enums\StatusPeriode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OsisPeriod>
 */
class OsisPeriodFactory extends Factory
{
    public function definition(): array
    {
        $tahun = $this->faker->unique()->numberBetween(2000, 2099);

        return [
            'nama' => $tahun.'/'.($tahun + 1),
            'mulai' => $tahun.'-07-14',
            'selesai' => ($tahun + 1).'-06-26',
            'status' => StatusPeriode::Disiapkan,
        ];
    }

    public function aktif(): static
    {
        return $this->state(['status' => StatusPeriode::Aktif]);
    }

    public function arsip(): static
    {
        return $this->state(['status' => StatusPeriode::Arsip]);
    }

    /** Periode dengan nama tahun ajaran tertentu, mis. tahun(2026) -> 2026/2027. */
    public function tahun(int $tahun): static
    {
        return $this->state(['nama' => $tahun.'/'.($tahun + 1), 'mulai' => $tahun.'-07-14', 'selesai' => ($tahun + 1).'-06-26']);
    }
}
