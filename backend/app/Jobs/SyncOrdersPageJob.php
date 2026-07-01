<?php

namespace App\Jobs;

use App\Models\OrderItem;
use App\Repositories\AffiliateRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Services\FakeStore\FakeStoreClient;
use App\Services\FakeStore\FakeStoreMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Processa e persiste UMA página de pedidos (carts) da fakestoreapi.
 *
 * Idempotente: usa upsert pela chave natural external_id em orders e a chave
 * (order_id, product_id) em order_items. Rodar mais de uma vez não duplica
 * dados nem sobrescreve o status de domínio dos pedidos existentes.
 */
class SyncOrdersPageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $page,
        public int $perPage,
    ) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(
        FakeStoreClient $client,
        FakeStoreMapper $mapper,
        OrderRepository $orders,
        AffiliateRepository $affiliates,
        ProductRepository $products,
    ): void {
        $carts = $client->cartsPage($this->page, $this->perPage);

        if ($carts === []) {
            return;
        }

        // Resolve afiliados (FK obrigatória). Carts com afiliado desconhecido
        // são ignorados (dados de referência devem ter sido sincronizados antes).
        $affiliateExternalIds = array_values(array_unique(array_map(
            fn (array $cart) => (int) $cart['userId'],
            $carts,
        )));
        $affiliateMap = $affiliates->mapExternalIds($affiliateExternalIds);

        $orderRows = [];
        foreach ($carts as $cart) {
            $affiliateId = $affiliateMap[(int) $cart['userId']] ?? null;
            if ($affiliateId === null) {
                Log::warning('orders:sync — cart ignorado, afiliado não encontrado', [
                    'cart_id' => $cart['id'] ?? null,
                    'user_id' => $cart['userId'] ?? null,
                ]);

                continue;
            }
            $orderRows[] = $mapper->orderRow($cart, $affiliateId);
        }

        if ($orderRows === []) {
            return;
        }

        DB::transaction(function () use ($carts, $orderRows, $orders, $products, $mapper, $affiliateMap) {
            // 1) upsert dos pedidos (não sobrescreve status/total existentes).
            $orders->upsertOrders($orderRows);

            // 2) mapa external_id → id dos pedidos desta página.
            $orderExternalIds = array_column($orderRows, 'external_id');
            $orderIdMap = $orders->mapExternalIds($orderExternalIds);

            // 3) resolve produtos (id + preço) referenciados nos itens.
            $productExternalIds = [];
            foreach ($carts as $cart) {
                foreach ($cart['products'] ?? [] as $p) {
                    $productExternalIds[] = (int) $p['productId'];
                }
            }
            $productMap = $products->mapForItems(array_values(array_unique($productExternalIds)));

            // 4) monta e faz upsert dos itens.
            $itemRows = [];
            $affectedOrderIds = [];
            foreach ($carts as $cart) {
                $orderId = $orderIdMap[(int) $cart['id']] ?? null;
                if ($orderId === null) {
                    continue;
                }
                $affectedOrderIds[$orderId] = true;

                foreach ($cart['products'] ?? [] as $cartProduct) {
                    $product = $productMap[(int) $cartProduct['productId']] ?? null;
                    if ($product === null) {
                        continue; // produto desconhecido — ignora o item.
                    }
                    $itemRows[] = $mapper->itemRow(
                        $orderId,
                        (int) $product->id,
                        $cartProduct,
                        (float) $product->price,
                    );
                }
            }

            if ($itemRows !== []) {
                OrderItem::query()->upsert(
                    $itemRows,
                    uniqueBy: ['order_id', 'product_id'],
                    update: ['quantity', 'unit_price', 'updated_at'],
                );
            }

            // 5) recalcula o total persistido dos pedidos afetados.
            $orders->recalculateTotals(array_keys($affectedOrderIds));
        });
    }
}
