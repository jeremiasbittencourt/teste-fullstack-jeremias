<?php

namespace App\Services\FakeStore;

use Illuminate\Support\Carbon;

/**
 * Traduz os payloads crus da fakestoreapi para linhas prontas de upsert.
 *
 * Domínio: cart = pedido, user = afiliado, product do cart = item de pedido.
 */
class FakeStoreMapper
{
    /**
     * @param  array<string, mixed>  $user
     * @return array<string, mixed>
     */
    public function affiliateRow(array $user): array
    {
        $name = $user['name'] ?? [];
        $fullName = trim(($name['firstname'] ?? '').' '.($name['lastname'] ?? ''));

        return [
            'external_id' => (int) $user['id'],
            'name' => $fullName !== '' ? $fullName : ($user['username'] ?? 'Afiliado #'.$user['id']),
            'email' => $user['email'] ?? null,
            'username' => $user['username'] ?? null,
            'phone' => $user['phone'] ?? null,
            'city' => $user['address']['city'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public function productRow(array $product): array
    {
        return [
            'external_id' => (int) $product['id'],
            'title' => $product['title'] ?? 'Produto #'.$product['id'],
            'price' => round((float) ($product['price'] ?? 0), 2),
            'category' => $product['category'] ?? null,
            'description' => $product['description'] ?? null,
            'image' => $product['image'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Linha de pedido (sem total — recalculado a partir dos itens).
     * created_at recebe a data do cart (data de negócio do pedido).
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    public function orderRow(array $cart, int $affiliateId): array
    {
        $date = isset($cart['date']) ? Carbon::parse($cart['date']) : now();

        return [
            'external_id' => (int) $cart['id'],
            'affiliate_id' => $affiliateId,
            'status' => \App\Enums\OrderStatus::Pending->value,
            'total' => 0,
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    /**
     * @param  array<string, mixed>  $cartProduct
     * @return array<string, mixed>
     */
    public function itemRow(int $orderId, int $productId, array $cartProduct, float $unitPrice): array
    {
        return [
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => (int) ($cartProduct['quantity'] ?? 1),
            'unit_price' => round($unitPrice, 2),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
