<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Mappers;

use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAssignmentModel;
use DateTimeImmutable;
use DateTimeInterface;

final class TicketAssignmentMapper
{
    public function toDomain(TicketAssignmentModel $model): TicketAssignment
    {
        return new TicketAssignment(
            id: $model->id,
            ticketId: $model->ticket_id,
            userId: $model->user_id,
            userName: $model->relationLoaded('user') ? $model->user->name : null,
            assignedAt: $this->toDate($model->assigned_at) ?? new DateTimeImmutable,
            unassignedAt: $this->toDate($model->unassigned_at),
            assignedBy: $model->assigned_by,
            unassignedBy: $model->unassigned_by,
            reason: $model->reason,
        );
    }

    private function toDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
