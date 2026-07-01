<?php

namespace App\Jobs;

use App\Services\FakeStore\FakeStoreClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Descobre quantas páginas de pedidos existem e dispara um Job por página.
 *
 * Roda depois de afiliados e produtos (via Bus::chain no comando orders:sync),
 * garantindo que as FKs existam antes de inserir pedidos.
 */
class DispatchOrderPagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $perPage) {}

    public function handle(FakeStoreClient $client): void
    {
        $total = $client->cartsTotal();
        $pages = (int) max(1, ceil($total / max(1, $this->perPage)));

        for ($page = 1; $page <= $pages; $page++) {
            SyncOrdersPageJob::dispatch($page, $this->perPage);
        }
    }
}
