<?php

namespace Database\Factories;

use App\Models\Odc;
use Illuminate\Database\Eloquent\Factories\Factory;

class OdcFactory extends Factory
{
    protected $model = Odc::class;

    public function definition(): array
    {
        return [
            'kode_odc' => 'ODC-'.strtoupper(fake()->unique()->bothify('??###')),
            'nama_odc' => fake()->city().' ODC',
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
