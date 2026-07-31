<?php

namespace Database\Factories;

use App\Models\WaDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

class WaDeviceFactory extends Factory
{
    protected $model = WaDevice::class;

    public function definition(): array
    {
        return [
            'device_name' => $this->faker->word().' Device',
            'session_name' => $this->faker->unique()->slug(),
            'status' => 'disconnected',
        ];
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'connected',
            'phone_number' => $this->faker->phoneNumber(),
            'profile_name' => $this->faker->name(),
            'connected_at' => now(),
            'last_seen' => now(),
        ]);
    }

    public function qrWaiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qr_waiting',
        ]);
    }
}
