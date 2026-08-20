<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $before = fake()->numberBetween(0, 100);

        return [
            'item_id' => Item::factory(),
            'stock_transaction_id' => StockTransaction::factory(),
            'type' => StockTransaction::TYPE_IN,
            'quantity' => fake()->numberBetween(1, 20),
            'stock_before' => $before,
            'stock_after' => $before + 10,
            'user_id' => User::factory(),
            'note' => null,
            'moved_at' => now(),
        ];
    }
}
