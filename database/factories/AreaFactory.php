<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        $areas = [
            ['JKT', 'Jakarta'],
            ['BGR', 'Bogor'],
            ['DPK', 'Depok'],
            ['BKS', 'Bekasi'],
            ['TGR', 'Tangerang'],
        ];

        $area = fake()->unique()->randomElement($areas);

        return [
            'code' => $area[0],
            'name' => $area[1],
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
