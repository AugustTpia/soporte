<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Mappers;

use App\Support\Domain\Entities\TicketActivity;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketActivityModel;
use DateTimeImmutable;
use DateTimeInterface;

final class TicketActivityMapper
{
    public function toDomain(TicketActivityModel $model): TicketActivity
    {
        return new TicketActivity(
            id: $model->id,
            ticketId: $model->ticket_id,
            actorId: $model->actor_id,
            actorName: $model->relationLoaded('actor') ? $model->actor?->name : null,
            type: TicketActivityType::from($model->type),
            body: $model->body,
            metadata: $model->metadata ?? [],
            createdAt: $this->toDate($model->created_at) ?? new DateTimeImmutable,
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
