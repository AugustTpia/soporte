<?php

namespace App\Maintenance\Domain\Enums;

use DateTimeImmutable;

enum MaintenanceIntervalUnit: string
{
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';

    public function label(): string
    {
        return match ($this) {
            self::Days => 'Días',
            self::Weeks => 'Semanas',
            self::Months => 'Meses',
        };
    }

    public function addTo(DateTimeImmutable $date, int $value): DateTimeImmutable
    {
        return $date->modify(sprintf('+%d %s', max(1, $value), $this->value));
    }
}
