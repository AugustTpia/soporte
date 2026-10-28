<?php

namespace App\Support\Domain\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::Assigned => 'Asignado',
            self::InProgress => 'En progreso',
            self::Waiting => 'En espera',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'blue',
            self::Assigned => 'indigo',
            self::InProgress => 'cyan',
            self::Waiting => 'amber',
            self::Resolved => 'green',
            self::Closed => 'zinc',
            self::Cancelled => 'red',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Open, self::InProgress, self::Waiting, self::Cancelled],
            self::InProgress => [self::Waiting, self::Resolved, self::Cancelled],
            self::Waiting => [self::InProgress, self::Resolved, self::Cancelled],
            self::Resolved => [self::InProgress, self::Closed],
            self::Closed => [self::InProgress],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return $this === $target || in_array($target, $this->allowedTransitions(), true);
    }

    public function requiresActiveAssignee(): bool
    {
        return in_array($this, [self::Assigned, self::InProgress, self::Waiting], true);
    }

    public function isUnresolved(): bool
    {
        return in_array($this, [self::Open, self::Assigned, self::InProgress, self::Waiting], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }

    public function requiresReason(?self $from = null): bool
    {
        if ($this === self::Cancelled) {
            return true;
        }

        return $this === self::InProgress
            && $from !== null
            && in_array($from, [self::Resolved, self::Closed], true);
    }
}
