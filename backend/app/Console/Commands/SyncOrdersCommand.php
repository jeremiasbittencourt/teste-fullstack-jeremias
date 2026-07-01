<?php

namespace App\Console\Commands;

use App\Jobs\DispatchOrderPagesJob;
use App\Jobs\SyncAffiliatesJob;
use App\Jobs\SyncProductsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncOrdersCommand extends Command
{
    protected $signature = 'orders:sync
                            {--page-size= : Quantidade de pedidos por página/Job}';

    protected $description = 'Importa afiliados, produtos e pedidos da fakestoreapi de forma assíncrona e idempotente';

    public function handle(): int
    {
        $perPage = (int) ($this->option('page-size') ?: config('services.fakestore.page_size', 50));

        // Ordem garantida: afiliados → produtos → (dispara N jobs de pedidos).
        // Assim as FKs de orders (affiliate_id) e order_items (product_id) já existem.
        Bus::chain([
            new SyncAffiliatesJob,
            new SyncProductsJob,
            new DispatchOrderPagesJob($perPage),
        ])->dispatch();

        $this->info('Sincronização enfileirada. Rode "php artisan queue:work" para processar.');
        $this->line("Tamanho de página: {$perPage}");

        return self::SUCCESS;
    }
}
