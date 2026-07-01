<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeFakeStore(): void
    {
        Http::fake([
            'fakestoreapi.com/users' => Http::response([
                ['id' => 1, 'email' => 'a@x.com', 'username' => 'ana', 'name' => ['firstname' => 'Ana', 'lastname' => 'Silva'], 'phone' => '111', 'address' => ['city' => 'Recife']],
                ['id' => 2, 'email' => 'b@x.com', 'username' => 'bob', 'name' => ['firstname' => 'Bob', 'lastname' => 'Souza'], 'phone' => '222', 'address' => ['city' => 'Olinda']],
            ]),
            'fakestoreapi.com/products' => Http::response([
                ['id' => 10, 'title' => 'Camiseta', 'price' => 100.00, 'category' => "men's clothing", 'description' => 'x', 'image' => 'http://img/1'],
                ['id' => 20, 'title' => 'Anel', 'price' => 50.00, 'category' => 'jewelery', 'description' => 'y', 'image' => 'http://img/2'],
            ]),
            'fakestoreapi.com/carts' => Http::response([
                ['id' => 1, 'userId' => 1, 'date' => '2024-01-10', 'products' => [['productId' => 10, 'quantity' => 2], ['productId' => 20, 'quantity' => 1]]],
                ['id' => 2, 'userId' => 2, 'date' => '2024-02-15', 'products' => [['productId' => 10, 'quantity' => 1]]],
            ]),
        ]);
    }

    public function test_sync_persists_affiliates_products_orders_and_items(): void
    {
        $this->fakeFakeStore();

        $this->artisan('orders:sync')->assertSuccessful();

        $this->assertDatabaseCount('affiliates', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 3);

        // total do cart 1 = 2*100 + 1*50 = 250; status inicial = pending.
        $order = Order::where('external_id', 1)->first();
        $this->assertNotNull($order);
        $this->assertEquals('250.00', $order->total);
        $this->assertSame('pending', $order->status->value);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->fakeFakeStore();

        $this->artisan('orders:sync')->assertSuccessful();
        $this->artisan('orders:sync')->assertSuccessful();

        // Rodar duas vezes não duplica nada.
        $this->assertDatabaseCount('affiliates', 2);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 3);
    }
}
