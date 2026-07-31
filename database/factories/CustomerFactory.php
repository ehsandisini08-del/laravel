<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUST-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'name' => fake()->name(),
            'address' => fake()->address(),
            'phone' => fake()->unique()->phoneNumber(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'area_id' => Area::factory(),
            'router_id' => Router::factory(),
            'package_id' => Package::factory(),
            'ppp_secret_id' => null,
            'ppp_username' => fake()->unique()->userName(),
            'ppp_password' => fake()->password(8),
            'installation_date' => fake()->date(),
            'due_day' => fake()->numberBetween(1, 31),
            'isolation_day' => null,
            'status' => CustomerStatus::Active->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => CustomerStatus::Active->value]);
    }

    public function isolated(): static
    {
        return $this->state(fn () => ['status' => CustomerStatus::Isolated->value]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => CustomerStatus::Suspended->value]);
    }

    public function terminated(): static
    {
        return $this->state(fn () => ['status' => CustomerStatus::Terminated->value]);
    }
}
