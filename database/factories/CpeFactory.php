<?php

namespace Database\Factories;

use App\Models\Cpe;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cpe>
 */
class CpeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'genieacs_id' => 'GW-'.$this->faker->unique()->regexify('[A-Z0-9]{16}'),
            'customer_id' => null,
            'ppp_username' => $this->faker->unique()->userName(),
            'serial_number' => $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'manufacturer' => $this->faker->randomElement(['Huawei', 'ZTE', 'FiberHome', 'TP-Link']),
            'model_name' => $this->faker->randomElement(['HG8145V5', 'F660', 'AN5506', 'GPON ONT']),
            'model_number' => $this->faker->randomElement(['HG8145V5', 'ZXHN F660', 'AN5506-04']),
            'hardware_version' => $this->faker->bothify('HW###.#'),
            'software_version' => $this->faker->bothify('V#.#.#C#S###'),
            'ip_address' => $this->faker->localIpv4(),
            'mac_address' => $this->faker->macAddress(),
            'status' => Cpe::STATUS_ONLINE,
            'last_inform_at' => now(),
            'uptime' => $this->faker->numberBetween(60, 86400 * 30),
            'signal_parameters' => null,
            'tags' => [],
            'synced_at' => now(),
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'status' => Cpe::STATUS_ONLINE,
            'last_inform_at' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => Cpe::STATUS_OFFLINE,
            'last_inform_at' => now()->subHour(),
        ]);
    }

    public function linked(): static
    {
        return $this->state(fn () => [
            'customer_id' => Customer::factory(),
        ]);
    }
}
