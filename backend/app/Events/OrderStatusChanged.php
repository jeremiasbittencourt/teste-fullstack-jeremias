<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Disparado após uma transição de status bem-sucedida no OrderService.
 *
 * Consumido por um listener que enfileira o envio ao N8N (ver Etapa 5),
 * mantendo a notificação desacoplada do fluxo principal.
 */
class OrderStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $previousStatus,
        public readonly OrderStatus $newStatus,
        public readonly Carbon $occurredAt,
    ) {}

    /**
     * Contrato do payload enviado ao N8N.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return [
            'event' => 'order.status_changed',
            'order_id' => $this->order->id,
            'affiliate_id' => $this->order->affiliate_id,
            'previous_status' => $this->previousStatus->value,
            'new_status' => $this->newStatus->value,
            'total_value' => (float) $this->order->total,
            'occurred_at' => $this->occurredAt->toIso8601String(),
        ];
    }
}
