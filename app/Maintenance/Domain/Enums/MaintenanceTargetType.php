<?php

namespace App\Maintenance\Domain\Enums;

enum MaintenanceTargetType: string
{
    case Asset = 'asset';
    case Location = 'location';
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Activo',
            self::Location => 'Ubicación',
            self::Company => 'Empresa',
        };
    }
}
