<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    /** Colunas permitidas para ordenação (evita SQL injection via sort_by). */
    private const SORTABLE = ['id', 'total', 'status', 'created_at'];

    /**
     * Lista paginada de pedidos aplicando filtros e ordenação.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::query()->with('affiliate:id,name,external_id');

        $this->applyFilters($query, $filters);

        $sortBy = in_array($filters['sort_by'] ?? null, self::SORTABLE, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    /**
     * @param  Builder<Order>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['affiliate_id'] ?? null, fn (Builder $q, $v) => $q->where('affiliate_id', $v))
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->when(isset($filters['min_value']), fn (Builder $q) => $q->where('total', '>=', $filters['min_value']))
            ->when(isset($filters['max_value']), fn (Builder $q) => $q->where('total', '<=', $filters['max_value']));
    }

    public function findWithDetails(int $id): ?Order
    {
        return Order::query()
            ->with([
                'affiliate',
                'items.product:id,external_id,title,price',
                'statusLogs.user:id,name',
            ])
            ->find($id);
    }

    /**
     * Métricas agregadas para o dashboard. Uma única passada no banco.
     *
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        $revenueStatuses = [OrderStatus::Approved->value, OrderStatus::Refunded->value];

        $row = Order::query()
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending", [OrderStatus::Pending->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved", [OrderStatus::Approved->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled", [OrderStatus::Cancelled->value])
            ->selectRaw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as refunded", [OrderStatus::Refunded->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN total ELSE 0 END), 0) as revenue', $revenueStatuses)
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as revenue_orders', $revenueStatuses)
            ->first();

        $totalOrders = (int) $row->total_orders;
        $revenue = (float) $row->revenue;
        $revenueOrders = (int) $row->revenue_orders;
        $cancelled = (int) $row->cancelled;

        return [
            'total_orders' => $totalOrders,
            'by_status' => [
                'pending' => (int) $row->pending,
                'approved' => (int) $row->approved,
                'cancelled' => $cancelled,
                'refunded' => (int) $row->refunded,
            ],
            'total_revenue' => round($revenue, 2),
            'average_ticket' => $revenueOrders > 0 ? round($revenue / $revenueOrders, 2) : 0.0,
            'cancellation_rate' => $totalOrders > 0 ? round($cancelled / $totalOrders * 100, 2) : 0.0,
        ];
    }

    /**
     * Upsert idempotente de pedidos pela chave natural external_id.
     * Em conflito, NÃO sobrescreve status/total (domínio nosso, não da API).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function upsertOrders(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        Order::query()->upsert(
            $rows,
            uniqueBy: ['external_id'],
            update: ['affiliate_id', 'created_at', 'updated_at'],
        );
    }

    /**
     * Recalcula e persiste o total dos pedidos a partir dos itens.
     *
     * @param  array<int, int>  $orderIds
     */
    public function recalculateTotals(array $orderIds): void
    {
        if ($orderIds === []) {
            return;
        }

        DB::table('orders')
            ->whereIn('id', $orderIds)
            ->update([
                'total' => DB::raw('(SELECT COALESCE(SUM(quantity * unit_price), 0) FROM order_items WHERE order_items.order_id = orders.id)'),
            ]);
    }

    /**
     * Mapeia external_id → id interno para um conjunto de pedidos.
     *
     * @param  array<int, int>  $externalIds
     * @return array<int, int>
     */
    public function mapExternalIds(array $externalIds): array
    {
        return Order::query()
            ->whereIn('external_id', $externalIds)
            ->pluck('id', 'external_id')
            ->all();
    }
}
