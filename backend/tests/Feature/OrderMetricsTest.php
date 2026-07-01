<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function seedOrders(): Affiliate
    {
        $affiliate = Affiliate::factory()->create();

        Order::factory()->for($affiliate)->status(OrderStatus::Approved)->create(['total' => 100]);
        Order::factory()->for($affiliate)->status(OrderStatus::Approved)->create(['total' => 100]);
        Order::factory()->for($affiliate)->status(OrderStatus::Refunded)->create(['total' => 50]);
        Order::factory()->for($affiliate)->status(OrderStatus::Cancelled)->create(['total' => 30]);
        Order::factory()->for($affiliate)->status(OrderStatus::Pending)->create(['total' => 10]);

        return $affiliate;
    }

    public function test_metrics_returns_aggregated_values(): void
    {
        Cache::forget(OrderService::METRICS_CACHE_KEY);
        $this->seedOrders();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/orders/metrics');

        $response->assertOk()
            ->assertJsonPath('data.total_orders', 5)
            ->assertJsonPath('data.total_revenue', 250)
            ->assertJsonPath('data.average_ticket', 83.33)
            ->assertJsonPath('data.cancellation_rate', 20)
            ->assertJsonPath('data.by_status.approved', 2)
            ->assertJsonPath('data.by_status.pending', 1)
            ->assertJsonPath('data.by_status.cancelled', 1)
            ->assertJsonPath('data.by_status.refunded', 1);
    }

    public function test_metrics_are_cached_and_invalidated_on_status_change(): void
    {
        Cache::forget(OrderService::METRICS_CACHE_KEY);
        $affiliate = $this->seedOrders();
        Sanctum::actingAs(User::factory()->create());

        // 1ª chamada: calcula e cacheia (5 pedidos).
        $this->getJson('/api/orders/metrics')->assertJsonPath('data.total_orders', 5);

        // Cria um pedido direto no banco (sem passar pelo service).
        $pending = Order::factory()->for($affiliate)->status(OrderStatus::Pending)->create(['total' => 5]);

        // 2ª chamada: ainda vem do cache (não reflete o novo pedido).
        $this->getJson('/api/orders/metrics')->assertJsonPath('data.total_orders', 5);

        // Uma transição de status invalida o cache de métricas.
        $this->postJson("/api/orders/{$pending->id}/status", ['status' => 'approved'])->assertOk();

        // 3ª chamada: cache reconstruído, agora com 6 pedidos.
        $this->getJson('/api/orders/metrics')->assertJsonPath('data.total_orders', 6);
    }
}
