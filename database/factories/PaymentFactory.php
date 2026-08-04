<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_method' => PaymentMethod::Cash,
            'gateway_provider' => 'manual',
            'reference' => null,
            'gateway_status' => null,
            'amount' => fake()->randomFloat(2, 50000, 500000),
            'status' => PaymentStatus::Success,
            'paid_by_user_id' => null,
            'notes' => null,
            'payload' => null,
            'paid_at' => now(),
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'payment_method' => PaymentMethod::Cash,
            'gateway_provider' => 'manual',
        ]);
    }

    public function gateway(string $provider, PaymentMethod $method, string $reference): static
    {
        return $this->state(fn () => [
            'payment_method' => $method,
            'gateway_provider' => $provider,
            'reference' => $reference,
        ]);
    }
}
