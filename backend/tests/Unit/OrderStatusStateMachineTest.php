<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Support\OrderStatusStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderStatusStateMachineTest extends TestCase
{
    public static function validTransitions(): array
    {
        return [
            'pending → approved' => [OrderStatus::Pending, OrderStatus::Approved],
            'pending → cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled],
            'approved → refunded' => [OrderStatus::Approved, OrderStatus::Refunded],
        ];
    }

    #[DataProvider('validTransitions')]
    public function test_allows_valid_transitions(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertTrue(OrderStatusStateMachine::canTransition($from, $to));

        // Não deve lançar.
        OrderStatusStateMachine::assertCanTransition($from, $to);
        $this->addToAssertionCount(1);
    }

    public static function invalidTransitions(): array
    {
        return [
            'pending → refunded' => [OrderStatus::Pending, OrderStatus::Refunded],
            'approved → cancelled' => [OrderStatus::Approved, OrderStatus::Cancelled],
            'approved → pending' => [OrderStatus::Approved, OrderStatus::Pending],
            'cancelled → approved' => [OrderStatus::Cancelled, OrderStatus::Approved],
            'refunded → approved' => [OrderStatus::Refunded, OrderStatus::Approved],
        ];
    }

    #[DataProvider('invalidTransitions')]
    public function test_rejects_invalid_transitions(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertFalse(OrderStatusStateMachine::canTransition($from, $to));

        $this->expectException(InvalidStatusTransitionException::class);
        OrderStatusStateMachine::assertCanTransition($from, $to);
    }

    public function test_final_states_have_no_transitions(): void
    {
        $this->assertSame([], OrderStatusStateMachine::allowedTransitions(OrderStatus::Cancelled));
        $this->assertSame([], OrderStatusStateMachine::allowedTransitions(OrderStatus::Refunded));
    }
}
