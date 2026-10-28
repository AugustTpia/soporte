<?php

namespace App\BusTicketCancellations\Domain\Entities;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final class BusTicketCancellation
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public ?int $supportTicketId,
        public string $ticketNumber,
        public DateTimeImmutable $requestedAt,
        public ?DateTimeImmutable $executedAt,
        public ?DateTimeImmutable $originalTicketAt,
        public ?int $amountCents,
        public int $operatorId,
        public BusTicketCancellationResult $result,
        public string $reason,
        public ?string $authorizationReference,
        public ?string $evidenceNotes,
        public ?int $createdBy,
        public ?string $operatorName = null,
        public ?string $supportTicketFolio = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {
        $this->guard();
    }

    public static function create(
        int $companyId,
        ?int $supportTicketId,
        string $ticketNumber,
        DateTimeImmutable $requestedAt,
        ?DateTimeImmutable $executedAt,
        ?DateTimeImmutable $originalTicketAt,
        ?int $amountCents,
        int $operatorId,
        BusTicketCancellationResult $result,
        string $reason,
        ?string $authorizationReference,
        ?string $evidenceNotes,
        ?int $createdBy,
    ): self {
        return new self(
            id: null,
            companyId: $companyId,
            supportTicketId: $supportTicketId,
            ticketNumber: $ticketNumber,
            requestedAt: $requestedAt,
            executedAt: $executedAt,
            originalTicketAt: $originalTicketAt,
            amountCents: $amountCents,
            operatorId: $operatorId,
            result: $result,
            reason: $reason,
            authorizationReference: $authorizationReference,
            evidenceNotes: $evidenceNotes,
            createdBy: $createdBy,
        );
    }

    public function updateDetails(
        ?int $supportTicketId,
        string $ticketNumber,
        DateTimeImmutable $requestedAt,
        ?DateTimeImmutable $executedAt,
        ?DateTimeImmutable $originalTicketAt,
        ?int $amountCents,
        int $operatorId,
        BusTicketCancellationResult $result,
        string $reason,
        ?string $authorizationReference,
        ?string $evidenceNotes,
    ): void {
        $this->supportTicketId = $supportTicketId;
        $this->ticketNumber = $ticketNumber;
        $this->requestedAt = $requestedAt;
        $this->executedAt = $executedAt;
        $this->originalTicketAt = $originalTicketAt;
        $this->amountCents = $amountCents;
        $this->operatorId = $operatorId;
        $this->result = $result;
        $this->reason = $reason;
        $this->authorizationReference = $authorizationReference;
        $this->evidenceNotes = $evidenceNotes;

        $this->guard();
    }

    /**
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'support_ticket_id' => $this->supportTicketId,
            'ticket_number' => $this->ticketNumber,
            'requested_at' => $this->requestedAt->format('Y-m-d H:i:s'),
            'executed_at' => $this->executedAt?->format('Y-m-d H:i:s'),
            'original_ticket_at' => $this->originalTicketAt?->format('Y-m-d H:i:s'),
            'amount_cents' => $this->amountCents,
            'operator_id' => $this->operatorId,
            'result' => $this->result->value,
            'reason' => $this->reason,
            'authorization_reference' => $this->authorizationReference,
            'evidence_notes' => $this->evidenceNotes,
        ];
    }

    private function guard(): void
    {
        $this->ticketNumber = trim($this->ticketNumber);
        $this->reason = trim($this->reason);
        $this->authorizationReference = $this->nullable($this->authorizationReference);
        $this->evidenceNotes = $this->nullable($this->evidenceNotes);

        if ($this->companyId <= 0) {
            throw DomainException::because('La empresa de la cancelación es obligatoria.');
        }

        if ($this->ticketNumber === '') {
            throw DomainException::because('El número de boleto es obligatorio.');
        }

        if ($this->operatorId <= 0) {
            throw DomainException::because('El operador responsable es obligatorio.');
        }

        if ($this->supportTicketId !== null && $this->supportTicketId <= 0) {
            throw DomainException::because('El ticket de soporte vinculado no es válido.');
        }

        if ($this->amountCents !== null && $this->amountCents < 0) {
            throw DomainException::because('El importe no puede ser negativo.');
        }

        if ($this->amountCents !== null && $this->amountCents > 99999999999) {
            throw DomainException::because('El importe supera el máximo permitido.');
        }

        if ($this->reason === '') {
            throw DomainException::because('El motivo de la cancelación es obligatorio.');
        }

        if ($this->executedAt !== null && $this->executedAt < $this->requestedAt) {
            throw DomainException::because('La fecha de ejecución no puede ser anterior a la solicitud.');
        }

        if ($this->result->requiresExecution() && $this->executedAt === null) {
            throw DomainException::because('La fecha de ejecución es obligatoria para el resultado seleccionado.');
        }

        if ($this->result === BusTicketCancellationResult::Pending && $this->executedAt !== null) {
            throw DomainException::because('Un registro pendiente no puede tener fecha de ejecución.');
        }

        $this->ensureMax($this->ticketNumber, 100, 'El número de boleto no puede superar 100 caracteres.');
        $this->ensureMax($this->reason, 2000, 'El motivo no puede superar 2000 caracteres.');
        $this->ensureMax($this->authorizationReference, 150, 'La referencia de autorización no puede superar 150 caracteres.');
        $this->ensureMax($this->evidenceNotes, 2000, 'Las notas de evidencia no pueden superar 2000 caracteres.');
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ensureMax(?string $value, int $max, string $message): void
    {
        if ($value !== null && mb_strlen($value) > $max) {
            throw DomainException::because($message);
        }
    }
}
