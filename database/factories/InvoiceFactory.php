<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'package_id' => Package::factory(),
            'router_id' => Router::factory(),
            'billing_month' => fake()->numberBetween(1, 12),
            'billing_year' => fake()->numberBetween(2024, 2026),
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'due_day' => 10,
            'isolation_day' => 15,
            'due_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'status' => InvoiceStatus::Unpaid,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Unpaid]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Overdue]);
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => InvoiceStatus::Paid]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => ['due_date' => now()->subDays(2)]);
    }
}
