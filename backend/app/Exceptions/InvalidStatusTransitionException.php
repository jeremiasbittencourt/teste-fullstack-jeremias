<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidStatusTransitionException extends Exception
{
    public function __construct(
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly array $allowed = [],
    ) {
        parent::__construct(sprintf(
            'Transição de status inválida: "%s" → "%s".',
            $from->value,
            $to->value,
        ));
    }

    /**
     * Renderiza no formato padrão da API ({ data, meta, errors }) com HTTP 422.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => null,
            'errors' => [
                'status' => [
                    $this->getMessage(),
                ],
                'allowed_transitions' => array_map(fn (OrderStatus $s) => $s->value, $this->allowed),
            ],
        ], 422);
    }
}
