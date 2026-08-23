<?php

namespace Database\Factories;

use App\Enums\RepairTaskStatus;
use App\Models\Customer;
use App\Models\RepairTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairTask>
 */
class RepairTaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'assigned_by_user_id' => User::factory(),
            'taken_by_user_id' => null,
            'nama_customer' => fake()->name(),
            'alamat' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'no_telp' => fake()->phoneNumber(),
            'keterangan' => fake()->paragraph(3),
            'keterangan_teknisi' => null,
            'status' => RepairTaskStatus::Baru,
            'foto_bukti' => null,
            'taken_at' => null,
            'completed_at' => null,
        ];
    }

    public function proses(): static
    {
        return $this->state(fn (array $attributes) => [
            'taken_by_user_id' => User::factory(),
            'status' => RepairTaskStatus::Proses,
            'taken_at' => now()->subHours(rand(1, 24)),
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'taken_by_user_id' => User::factory(),
            'keterangan_teknisi' => fake()->paragraph(2),
            'status' => RepairTaskStatus::Selesai,
            'taken_at' => now()->subHours(rand(24, 72)),
            'completed_at' => now()->subHours(rand(1, 23)),
        ]);
    }
}
