<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => str_pad((string) fake()->unique()->numberBetween(0, 999999), 6, '0', STR_PAD_LEFT),
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
            'portal_password' => null,
            'portal_enabled' => true,
            'installation_date' => fake()->date(),
            'due_day' => fake()->numberBetween(1, 31),
            'isolation_day' => null,
            'status' => CustomerStatus::Active->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function withPortal(string $password): static
    {
        return $this->state(fn () => [
            'portal_enabled' => true,
            'portal_password' => Hash::make($password),
            'portal_password_plain' => $password,
        ]);
    }

    public function withoutPortal(): static
    {
        return $this->state(fn () => [
            'portal_enabled' => false,
            'portal_password' => null,
        ]);
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
