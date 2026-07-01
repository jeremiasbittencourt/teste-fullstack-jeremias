<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Support\OrderStatusStateMachine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
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
            'affiliate' => $this->whenLoaded('affiliate', fn () => [
                'id' => $this->affiliate->id,
                'name' => $this->affiliate->name,
            ]),
            'allowed_transitions' => $this->allowedTransitions($this->status),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function allowedTransitions(OrderStatus $status): array
    {
        return array_map(
            fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            OrderStatusStateMachine::allowedTransitions($status),
        );
    }
}
