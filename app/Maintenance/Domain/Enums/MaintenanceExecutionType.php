<?php

namespace App\Maintenance\Domain\Enums;

enum MaintenanceExecutionType: string
{
    case Preventive = 'preventive';
    case Corrective = 'corrective';

    public function label(): string
    {
        return match ($this) {
            self::Preventive => 'Preventivo',
            self::Corrective => 'Correctivo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Preventive => 'blue',
            self::Corrective => 'amber',
        };
    }
}
