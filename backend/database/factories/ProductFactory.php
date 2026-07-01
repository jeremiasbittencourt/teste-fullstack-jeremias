<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->numberBetween(1, 1_000_000),
            'title' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'category' => $this->faker->randomElement([
                "men's clothing", "women's clothing", 'electronics', 'jewelery',
            ]),
            'description' => $this->faker->sentence(),
            'image' => $this->faker->imageUrl(),
        ];
    }
}
