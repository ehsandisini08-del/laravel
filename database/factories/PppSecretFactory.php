<?php

namespace Database\Factories;

use App\Models\PppSecret;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PppSecret>
 */
class PppSecretFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'router_id' => Router::factory(),
            'mikrotik_id' => '*'.$this->faker->unique()->numberBetween(1, 9999),
            'name' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'service' => 'any',
            'profile' => 'default',
            'local_address' => $this->faker->localIpv4(),
            'remote_address' => $this->faker->localIpv4(),
            'caller_id' => null,
            'disabled' => false,
            'comment' => $this->faker->optional()->sentence(),
            'last_logged_out' => null,
        ];
    }
}
