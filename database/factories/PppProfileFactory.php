<?php

namespace Database\Factories;

use App\Models\PppProfile;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class PppProfileFactory extends Factory
{
    protected $model = PppProfile::class;

    public function definition(): array
    {
        return [
            'router_id' => Router::factory(),
            'mikrotik_id' => '*'.fake()->unique()->numberBetween(100, 9999),
            'name' => fake()->unique()->word().'-profile',
            'local_address' => fake()->optional()->ipv4(),
            'remote_address' => fake()->optional()->ipv4(),
            'dns_server' => fake()->optional()->ipv4(),
            'rate_limit' => fake()->optional()->randomElement(['10M/10M', '20M/10M', '5M/5M', '100M/100M']),
            'parent_queue' => fake()->optional()->word(),
            'only_one' => fake()->boolean(),
            'change_tcp_mss' => fake()->boolean(),
            'use_compression' => fake()->boolean(),
            'use_encryption' => fake()->boolean(),
            'use_ipv6' => fake()->boolean(),
            'bridge' => fake()->optional()->word(),
            'bridge_path_cost' => fake()->optional()->numberBetween(1, 100),
            'bridge_horizon' => fake()->optional()->word(),
            'comment' => fake()->optional()->sentence(),
            'synced_at' => now(),
        ];
    }

    public function forRouter(Router $router): static
    {
        return $this->state(['router_id' => $router->id]);
    }
}
