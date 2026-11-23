<?php

namespace App\Projects\Domain\Enums;

enum ProjectTaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En proceso',
            self::Blocked => 'Bloqueada',
            self::Completed => 'Completada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::InProgress => 'cyan',
            self::Blocked => 'rose',
            self::Completed => 'green',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Blocked, self::Completed],
            self::InProgress => [self::Pending, self::Blocked, self::Completed],
            self::Blocked => [self::Pending, self::InProgress],
            self::Completed => [self::InProgress],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Completed;
    }
}
