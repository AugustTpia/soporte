<?php

namespace App\Credentials\Domain\Enums;

enum CredentialStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Revoked => 'Revocada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Revoked => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Active => [self::Revoked],
            self::Revoked => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }
}
