<?php

namespace App\Support\Domain\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Urgent => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'blue',
            self::High => 'amber',
            self::Urgent => 'red',
        };
    }
}
