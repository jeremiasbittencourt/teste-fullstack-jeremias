<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        // created_at = data do pedido, distribuída nos últimos 6 meses para
        // dar sentido a métricas/cohort.
        $createdAt = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'external_id' => $this->faker->unique()->numberBetween(1, 1_000_000),
            'affiliate_id' => Affiliate::factory(),
            'status' => $this->faker->randomElement(OrderStatus::values()),
            'total' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
