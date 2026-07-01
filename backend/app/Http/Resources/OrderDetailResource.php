<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Support\OrderStatusStateMachine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detalhe do pedido com itens e histórico de status.
 *
 * @mixin \App\Models\Order
 */
class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'total' => (float) $this->total,
            'created_at' => $this->created_at?->toIso8601String(),
            'affiliate' => $this->whenLoaded('affiliate', fn () => [
                'id' => $this->affiliate->id,
                'name' => $this->affiliate->name,
                'email' => $this->affiliate->email,
                'city' => $this->affiliate->city,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'title' => $item->product?->title,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
            ])->all()),
            'status_logs' => $this->whenLoaded('statusLogs', fn () => $this->statusLogs->map(fn ($log) => [
                'id' => $log->id,
                'previous_status' => $log->previous_status,
                'new_status' => $log->new_status,
                'changed_by' => $log->user?->name,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all()),
            'allowed_transitions' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                OrderStatusStateMachine::allowedTransitions($this->status),
            ),
        ];
    }
}
