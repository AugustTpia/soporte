<?php

namespace App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Mappers;

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;

final class BusTicketCancellationMapper
{
    public function toDomain(BusTicketCancellationModel $model): BusTicketCancellation
    {
        return new BusTicketCancellation(
            id: $model->id,
            companyId: $model->company_id,
            supportTicketId: $model->support_ticket_id,
            ticketNumber: $model->ticket_number,
            requestedAt: $this->toDate($model->requested_at) ?? new DateTimeImmutable,
            executedAt: $this->toDate($model->executed_at),
            originalTicketAt: $this->toDate($model->original_ticket_at),
            amountCents: $model->amount_cents,
            operatorId: $model->operator_id,
            result: BusTicketCancellationResult::from($model->result),
            reason: $model->reason,
            authorizationReference: $model->authorization_reference,
            evidenceNotes: $model->evidence_notes,
            createdBy: $model->created_by,
            operatorName: $model->relationLoaded('operator') ? $model->operator->name : null,
            supportTicketFolio: $model->relationLoaded('supportTicket') ? $model->supportTicket?->folio : null,
            createdAt: $this->toDate($model->created_at),
        );
    }

    public function fillModel(
        BusTicketCancellation $cancellation,
        BusTicketCancellationModel $model,
    ): BusTicketCancellationModel {
        $model->company_id = $cancellation->companyId;
        $model->support_ticket_id = $cancellation->supportTicketId;
        $model->ticket_number = $cancellation->ticketNumber;
        $model->requested_at = CarbonImmutable::createFromInterface($cancellation->requestedAt);
        $model->executed_at = $this->toCarbon($cancellation->executedAt);
        $model->original_ticket_at = $this->toCarbon($cancellation->originalTicketAt);
        $model->amount_cents = $cancellation->amountCents;
        $model->operator_id = $cancellation->operatorId;
        $model->result = $cancellation->result->value;
        $model->reason = $cancellation->reason;
        $model->authorization_reference = $cancellation->authorizationReference;
        $model->evidence_notes = $cancellation->evidenceNotes;
        $model->created_by = $cancellation->createdBy;

        return $model;
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
