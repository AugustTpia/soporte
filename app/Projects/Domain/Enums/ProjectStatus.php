<?php

namespace App\Projects\Domain\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planificado',
            self::Active => 'Activo',
            self::Paused => 'En pausa',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'blue',
            self::Active => 'green',
            self::Paused => 'amber',
            self::Completed => 'zinc',
            self::Cancelled => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planned => [self::Active, self::Cancelled],
            self::Active => [self::Paused, self::Completed, self::Cancelled],
            self::Paused => [self::Active, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
