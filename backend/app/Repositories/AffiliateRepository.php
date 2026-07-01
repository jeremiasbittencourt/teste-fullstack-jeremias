<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Order;

class AffiliateRepository
{
    public function find(int $id): ?Affiliate
    {
        return Affiliate::find($id);
    }

    /**
     * Resumo do afiliado: total de pedidos, receita, ticket médio e taxa de cancelamento.
     *
     * @return array<string, mixed>
     */
    public function summary(int $affiliateId): array
    {
        $revenueStatuses = [OrderStatus::Approved->value, OrderStatus::Refunded->value];

        $row = Order::query()
            ->where('affiliate_id', $affiliateId)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN total ELSE 0 END), 0) as revenue', $revenueStatuses)
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as revenue_orders', $revenueStatuses)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled', [OrderStatus::Cancelled->value])
            ->first();

        $totalOrders = (int) $row->total_orders;
        $revenue = (float) $row->revenue;
        $revenueOrders = (int) $row->revenue_orders;
        $cancelled = (int) $row->cancelled;

        return [
            'total_orders' => $totalOrders,
            'revenue' => round($revenue, 2),
            'average_ticket' => $revenueOrders > 0 ? round($revenue / $revenueOrders, 2) : 0.0,
            'cancellation_rate' => $totalOrders > 0 ? round($cancelled / $totalOrders * 100, 2) : 0.0,
        ];
    }

    /**
     * Upsert idempotente de afiliados pela chave natural external_id.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        Affiliate::query()->upsert(
            $rows,
            uniqueBy: ['external_id'],
            update: ['name', 'email', 'username', 'phone', 'city', 'updated_at'],
        );
    }

    /**
     * @param  array<int, int>  $externalIds
     * @return array<int, int>  external_id => id
     */
    public function mapExternalIds(array $externalIds): array
    {
        return Affiliate::query()
            ->whereIn('external_id', $externalIds)
            ->pluck('id', 'external_id')
            ->all();
    }
}
