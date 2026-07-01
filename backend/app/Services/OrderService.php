<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Support\OrderStatusStateMachine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public const METRICS_CACHE_KEY = 'orders:metrics';

    public const METRICS_CACHE_TTL = 300; // 5 minutos

    public function __construct(
        private readonly OrderRepository $orders,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->orders->paginate($filters, $perPage);
    }

    public function find(int $id): ?Order
    {
        return $this->orders->findWithDetails($id);
    }

    /**
     * Transição de status respeitando a máquina de estados.
     * Registra o log de auditoria, invalida o cache de métricas e dispara o
     * evento de notificação. Transição inválida lança 422 (na state machine).
     */
    public function transitionStatus(Order $order, OrderStatus $newStatus, ?User $user = null): Order
    {
        $previous = $order->status;

        OrderStatusStateMachine::assertCanTransition($previous, $newStatus);

        $occurredAt = Carbon::now();

        DB::transaction(function () use ($order, $previous, $newStatus, $user, $occurredAt) {
            $order->update(['status' => $newStatus->value]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'previous_status' => $previous->value,
                'new_status' => $newStatus->value,
                'changed_by' => $user?->id,
                'created_at' => $occurredAt,
            ]);
        });

        // Não confiar apenas no TTL: invalida imediatamente ao mudar status.
        $this->invalidateMetricsCache();

        // Notificação desacoplada (listener → job → N8N).
        OrderStatusChanged::dispatch($order->refresh(), $previous, $newStatus, $occurredAt);

        return $order;
    }

    /**
     * Métricas agregadas com cache de 5 min (Redis). O payload carrega
     * generated_at para o frontend exibir "atualizado há X min".
     *
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        return Cache::remember(self::METRICS_CACHE_KEY, self::METRICS_CACHE_TTL, function () {
            return array_merge(
                $this->orders->metrics(),
                ['generated_at' => Carbon::now()->toIso8601String()],
            );
        });
    }

    public function invalidateMetricsCache(): void
    {
        Cache::forget(self::METRICS_CACHE_KEY);
    }
}
