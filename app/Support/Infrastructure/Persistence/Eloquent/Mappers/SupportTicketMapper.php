<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Mappers;

use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketActivityModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAssignmentModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class SupportTicketMapper
{
    public function __construct(
        private readonly TicketAssignmentMapper $assignments,
        private readonly TicketActivityMapper $activities,
    ) {}

    public function toDomain(SupportTicketModel $model): SupportTicket
    {
        return new SupportTicket(
            id: $model->id,
            companyId: $model->company_id,
            folio: $model->folio,
            category: TicketCategory::from($model->category),
            subject: $model->subject,
            description: $model->description,
            requesterName: $model->requester_name,
            requesterContact: $model->requester_contact,
            priority: TicketPriority::from($model->priority),
            status: TicketStatus::from($model->status),
            locationId: $model->location_id,
            assetId: $model->asset_id,
            serviceAccountId: $model->service_account_id,
            clientContactId: $model->client_contact_id,
            openedAt: $this->toDate($model->opened_at) ?? new DateTimeImmutable,
            dueAt: $this->toDate($model->due_at),
            resolvedAt: $this->toDate($model->resolved_at),
            closedAt: $this->toDate($model->closed_at),
            cancelledAt: $this->toDate($model->cancelled_at),
            cancellationReason: $model->cancellation_reason,
            createdBy: $model->created_by,
            locationName: $model->relationLoaded('location') ? $model->location?->name : null,
            assetLabel: $model->relationLoaded('asset') ? $this->assetLabel($model) : null,
            accountLabel: $model->relationLoaded('serviceAccount') ? $this->accountLabel($model) : null,
            clientContactName: $model->relationLoaded('clientContact') ? $model->clientContact?->name : null,
            creatorName: $model->relationLoaded('creator') ? $model->creator?->name : null,
            assignments: $model->relationLoaded('assignments')
                ? $model->assignments->map(fn (TicketAssignmentModel $assignment) => $this->assignments->toDomain($assignment))->all()
                : [],
            activities: $model->relationLoaded('activities')
                ? $model->activities->map(fn (TicketActivityModel $activity) => $this->activities->toDomain($activity))->all()
                : [],
        );
    }

    public function fillModel(SupportTicket $ticket, SupportTicketModel $model): SupportTicketModel
    {
        $model->company_id = $ticket->companyId;
        $model->folio = $ticket->folio;
        $model->location_id = $ticket->locationId;
        $model->asset_id = $ticket->assetId;
        $model->service_account_id = $ticket->serviceAccountId;
        $model->client_contact_id = $ticket->clientContactId;
        $model->category = $ticket->category->value;
        $model->subject = $ticket->subject;
        $model->description = $ticket->description;
        $model->requester_name = $ticket->requesterName;
        $model->requester_contact = $ticket->requesterContact;
        $model->priority = $ticket->priority->value;
        $model->status = $ticket->status->value;
        $model->opened_at = CarbonImmutable::createFromInterface($ticket->openedAt);
        $model->due_at = $this->toCarbon($ticket->dueAt);
        $model->resolved_at = $this->toCarbon($ticket->resolvedAt);
        $model->closed_at = $this->toCarbon($ticket->closedAt);
        $model->cancelled_at = $this->toCarbon($ticket->cancelledAt);
        $model->cancellation_reason = $ticket->cancellationReason;
        $model->created_by = $ticket->createdBy;

        return $model;
    }

    private function assetLabel(SupportTicketModel $model): ?string
    {
        if ($model->asset === null) {
            return null;
        }

        return $model->asset->internal_code.($model->asset->hostname !== null ? ' · '.$model->asset->hostname : '');
    }

    private function accountLabel(SupportTicketModel $model): ?string
    {
        if ($model->serviceAccount === null) {
            return null;
        }

        return $model->serviceAccount->username.' · '.$model->serviceAccount->instance;
    }

    private function toCarbon(?DateTimeImmutable $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::createFromInterface($value);
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
