<?php

namespace App\Maintenance\Domain\Enums;

enum MaintenancePlanStatus: string
{
    case Active = 'active';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Paused => 'Pausado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Paused => 'zinc',
        };
    }
}
