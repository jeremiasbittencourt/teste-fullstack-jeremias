<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Approved => 'Aprovado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Approved => 'green',
            self::Cancelled => 'red',
            self::Refunded => 'purple',
        };
    }

    /**
     * Status que contam como receita realizada (usado em métricas e rankings).
     */
    public function countsAsRevenue(): bool
    {
        return in_array($this, [self::Approved, self::Refunded], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
