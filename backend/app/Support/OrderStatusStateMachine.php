<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;

/**
 * Máquina de estados das transições de status do pedido.
 *
 * Encapsula em um único lugar TODAS as transições válidas, evitando espalhar
 * if/else pelo service. Transições permitidas:
 *   - pending  → approved
 *   - pending  → cancelled
 *   - approved → refunded
 */
final class OrderStatusStateMachine
{
    /**
     * Mapa de transições válidas: origem => [destinos permitidos].
     *
     * @var array<string, array<int, OrderStatus>>
     */
    private const TRANSITIONS = [
        OrderStatus::Pending->value => [OrderStatus::Approved, OrderStatus::Cancelled],
        OrderStatus::Approved->value => [OrderStatus::Refunded],
        OrderStatus::Cancelled->value => [],
        OrderStatus::Refunded->value => [],
    ];

    /**
     * Retorna os destinos válidos a partir de um status.
     *
     * @return array<int, OrderStatus>
     */
    public static function allowedTransitions(OrderStatus $from): array
    {
        return self::TRANSITIONS[$from->value] ?? [];
    }

    public static function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to, self::allowedTransitions($from), true);
    }

    /**
     * Garante que a transição é válida ou lança 422.
     *
     * @throws InvalidStatusTransitionException
     */
    public static function assertCanTransition(OrderStatus $from, OrderStatus $to): void
    {
        if (! self::canTransition($from, $to)) {
            throw new InvalidStatusTransitionException($from, $to, self::allowedTransitions($from));
        }
    }
}
