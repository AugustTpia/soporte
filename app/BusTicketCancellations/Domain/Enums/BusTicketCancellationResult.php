<?php

namespace App\BusTicketCancellations\Domain\Enums;

enum BusTicketCancellationResult: string
{
    case Pending = 'pending';
    case Cancelled = 'cancelled';
    case NotCancelled = 'not_cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Cancelled => 'Cancelado',
            self::NotCancelled => 'No cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Cancelled => 'green',
            self::NotCancelled => 'rose',
        };
    }

    public function requiresExecution(): bool
    {
        return $this !== self::Pending;
    }
}
