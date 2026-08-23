<?php

namespace Database\Factories;

use App\Models\RepairTask;
use App\Models\RepairTaskComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairTaskComment>
 */
class RepairTaskCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repair_task_id' => RepairTask::factory(),
            'user_id' => User::factory(),
            'comment' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'comment' => fake()->randomElement([
                'Tugas dibuat oleh Admin',
                'Tugas diambil oleh Teknisi',
                'Tugas diselesaikan',
            ]),
        ]);
    }
}
