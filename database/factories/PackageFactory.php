<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Package;
use App\Models\PppProfile;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true).' Paket',
            'price' => fake()->randomFloat(2, 50000, 500000),
            'router_id' => Router::factory(),
            'ppp_profile_id' => PppProfile::factory(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withAreas(int $count = 1): static
    {
        return $this->afterCreate(function ($package) use ($count) {
            $areas = Area::factory()->count($count)->create();
            $package->areas()->attach($areas->pluck('id'));
        });
    }
}
