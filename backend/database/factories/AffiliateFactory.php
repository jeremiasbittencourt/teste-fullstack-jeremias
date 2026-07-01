<?php

namespace Database\Factories;

use App\Models\Affiliate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affiliate>
 */
class AffiliateFactory extends Factory
{
    protected $model = Affiliate::class;

    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->numberBetween(1, 1_000_000),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'username' => $this->faker->unique()->userName(),
            'phone' => $this->faker->phoneNumber(),
            'city' => $this->faker->city(),
        ];
    }
}
