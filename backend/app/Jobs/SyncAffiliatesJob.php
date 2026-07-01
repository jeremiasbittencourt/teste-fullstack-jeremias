<?php

namespace App\Jobs;

use App\Repositories\AffiliateRepository;
use App\Services\FakeStore\FakeStoreClient;
use App\Services\FakeStore\FakeStoreMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sincroniza afiliados (users da fakestoreapi) com upsert idempotente.
 */
class SyncAffiliatesJob implements ShouldQueue
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
        AffiliateRepository $affiliates,
    ): void {
        $rows = array_map(
            fn (array $user) => $mapper->affiliateRow($user),
            $client->users(),
        );

        $affiliates->upsertMany($rows);
    }
}
