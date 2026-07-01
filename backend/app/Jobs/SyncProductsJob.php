<?php

namespace App\Jobs;

use App\Repositories\ProductRepository;
use App\Services\FakeStore\FakeStoreClient;
use App\Services\FakeStore\FakeStoreMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sincroniza produtos (products da fakestoreapi) com upsert idempotente.
 */
class SyncProductsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        FakeStoreClient $client,
        FakeStoreMapper $mapper,
        ProductRepository $products,
    ): void {
        $rows = array_map(
            fn (array $product) => $mapper->productRow($product),
            $client->products(),
        );

        $products->upsertMany($rows);
    }
}
