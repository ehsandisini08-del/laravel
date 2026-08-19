<?php

namespace Database\Factories;

use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdpFactory extends Factory
{
    protected $model = Odp::class;

    public function definition(): array
    {
        return [
            'odc_id' => Odc::factory(),
            'kode_odp' => 'ODP-'.strtoupper(fake()->unique()->bothify('??###')),
            'nama_odp' => fake()->city().' ODP',
            'latitude' => fake()->latitude(-10, 6),
            'longitude' => fake()->longitude(95, 141),
        ];
    }

    public function withoutCoordinates(): static
    {
        return $this->state(fn () => [
            'latitude' => null,
            'longitude' => null,
        ]);
    }
}
