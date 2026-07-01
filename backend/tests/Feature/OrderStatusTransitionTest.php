<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(OrderStatus $status): Order
    {
        return Order::factory()
            ->for(Affiliate::factory())
            ->status($status)
            ->create();
    }

    public function test_valid_transition_updates_status_logs_and_fires_event(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = $this->makeOrder(OrderStatus::Pending);

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'previous_status' => 'pending',
            'new_status' => 'approved',
            'changed_by' => $user->id,
        ]);

        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_invalid_transition_returns_422_and_keeps_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $order = $this->makeOrder(OrderStatus::Pending);

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'refunded', // pending → refunded é inválido
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['data', 'meta', 'errors' => ['status', 'allowed_transitions']]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('order_status_logs', 0);
    }

    public function test_status_must_be_a_valid_enum_value(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder(OrderStatus::Pending);

        $this->postJson("/api/orders/{$order->id}/status", ['status' => 'banana'])
            ->assertStatus(422);
    }
}
