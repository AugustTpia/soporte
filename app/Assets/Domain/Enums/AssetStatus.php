<?php

namespace App\Assets\Domain\Enums;

enum AssetStatus: string
{
    case Active = 'active';
    case InMaintenance = 'in_maintenance';
    case InStorage = 'in_storage';
    case Retired = 'retired';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::InMaintenance => 'En mantenimiento',
            self::InStorage => 'En resguardo',
            self::Retired => 'Retirado',
            self::Lost => 'Perdido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::InMaintenance => 'amber',
            self::InStorage => 'blue',
            self::Retired => 'zinc',
            self::Lost => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Active => [self::InMaintenance, self::InStorage, self::Retired, self::Lost],
            self::InMaintenance => [self::Active, self::Retired, self::Lost],
            self::InStorage => [self::Active, self::Retired, self::Lost],
            self::Lost => [self::Active, self::Retired],
            self::Retired => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    public function isLinkable(): bool
    {
        return in_array($this, [self::Active, self::InMaintenance, self::InStorage], true);
    }
}
