<?php

namespace App\Accounts\Domain\Enums;

enum ServiceAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Suspended => 'Suspendida',
            self::Revoked => 'Revocada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'amber',
            self::Revoked => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Active => [self::Suspended, self::Revoked],
            self::Suspended => [self::Active, self::Revoked],
            self::Revoked => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }
}
