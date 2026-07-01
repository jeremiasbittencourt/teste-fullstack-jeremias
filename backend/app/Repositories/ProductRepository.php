<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    /**
     * Upsert idempotente de produtos pela chave natural external_id.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        Product::query()->upsert(
            $rows,
            uniqueBy: ['external_id'],
            update: ['title', 'price', 'category', 'description', 'image', 'updated_at'],
        );
    }

    /**
     * @param  array<int, int>  $externalIds
     * @return array<int, int>  external_id => id
     */
    public function mapExternalIds(array $externalIds): array
    {
        return Product::query()
            ->whereIn('external_id', $externalIds)
            ->pluck('id', 'external_id')
            ->all();
    }

    /**
     * Resolve id interno e preço dos produtos por external_id (para montar itens).
     *
     * @param  array<int, int>  $externalIds
     * @return array<int, Product>  external_id => Product{id, price}
     */
    public function mapForItems(array $externalIds): array
    {
        return Product::query()
            ->whereIn('external_id', $externalIds)
            ->get(['id', 'external_id', 'price'])
            ->keyBy('external_id')
            ->all();
    }
}
