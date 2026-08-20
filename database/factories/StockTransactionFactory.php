<?php

namespace Database\Factories;

use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_number' => fake()->unique()->numerify('TRX-####'),
            'type' => StockTransaction::TYPE_IN,
            'reference' => fake()->optional()->bothify('PO-####'),
            'supplier' => null,
            'recipient' => null,
            'reason' => null,
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
            'transaction_date' => now(),
        ];
    }

    public function typeIn(): static
    {
        return $this->state([
            'type' => StockTransaction::TYPE_IN,
            'supplier' => fake()->company(),
        ]);
    }

    public function typeOut(): static
    {
        return $this->state([
            'type' => StockTransaction::TYPE_OUT,
            'recipient' => fake()->name(),
        ]);
    }

    public function typeAdjustment(): static
    {
        return $this->state([
            'type' => StockTransaction::TYPE_ADJUSTMENT,
            'reason' => fake()->sentence(),
        ]);
    }
}
