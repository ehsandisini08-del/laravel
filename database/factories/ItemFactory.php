<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $items = ['Modem ONT', 'Routerboard', 'Kabel UTP Cat6', 'Access Point', 'Antena Grid', 'Splitter Fiber', 'Power Supply', 'Switch 8 Port'];

        return [
            'code' => fake()->unique()->bothify('BRG-####'),
            'name' => fake()->unique()->randomElement($items),
            'category_id' => Category::factory(),
            'unit' => fake()->randomElement(['pcs', 'unit', 'box', 'meter', 'set']),
            'description' => fake()->optional()->sentence(),
            'min_stock' => fake()->numberBetween(1, 10),
            'current_stock' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function lowStock(): static
    {
        return $this->state(['current_stock' => 0, 'min_stock' => 5]);
    }

    public function outOfStock(): static
    {
        return $this->state(['current_stock' => 0]);
    }
}
