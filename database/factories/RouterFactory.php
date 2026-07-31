<?php

namespace Database\Factories;

use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Router>
 */
class RouterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->company().' Router',
            'description' => $this->faker->sentence(),
            'host' => $this->faker->localIpv4(),
            'api_port' => 8728,
            'api_ssl' => false,
            'username' => 'admin',
            'password' => 'password',
            'location' => $this->faker->city(),
            'timezone' => $this->faker->timezone(),
            'routeros_version' => '7.x',
            'board_name' => 'CHR',
            'identity' => $this->faker->word().'-router',
            'architecture' => 'x86_64',
            'cpu' => 'Intel',
            'total_memory' => 1073741824,
            'free_memory' => 536870912,
            'uptime' => '1d2h3m',
            'last_seen_at' => now(),
            'status' => 'online',
            'enabled' => true,
            'is_default' => false,
            'priority' => 0,
        ];
    }
}
