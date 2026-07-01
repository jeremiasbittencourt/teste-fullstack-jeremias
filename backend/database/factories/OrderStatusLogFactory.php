<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusLog>
 */
class OrderStatusLogFactory extends Factory
{
    protected $model = OrderStatusLog::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'previous_status' => null,
            'new_status' => $this->faker->randomElement(OrderStatus::values()),
            'changed_by' => null,
            'created_at' => now(),
        ];
    }
}
