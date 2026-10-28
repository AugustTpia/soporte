<?php

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\Shared\Domain\Exceptions\DomainException;

function makeBusCancellation(array $overrides = []): BusTicketCancellation
{
    $requestedAt = $overrides['requestedAt'] ?? new DateTimeImmutable('2026-10-27 10:00:00');

    return BusTicketCancellation::create(
        companyId: 1,
        supportTicketId: $overrides['supportTicketId'] ?? null,
        ticketNumber: $overrides['ticketNumber'] ?? '  BOL-123456  ',
        requestedAt: $requestedAt,
        executedAt: array_key_exists('executedAt', $overrides)
            ? $overrides['executedAt']
            : $requestedAt->modify('+30 minutes'),
        originalTicketAt: $overrides['originalTicketAt'] ?? $requestedAt->modify('+1 day'),
        amountCents: $overrides['amountCents'] ?? 125050,
        operatorId: $overrides['operatorId'] ?? 10,
        result: $overrides['result'] ?? BusTicketCancellationResult::Cancelled,
        reason: $overrides['reason'] ?? '  Solicitud del cliente.  ',
        authorizationReference: $overrides['authorizationReference'] ?? 'AUT-001',
        evidenceNotes: $overrides['evidenceNotes'] ?? null,
        createdBy: 10,
    );
}

test('a bus ticket cancellation trims its fields and stores cents', function () {
    $cancellation = makeBusCancellation();

    expect($cancellation->ticketNumber)->toBe('BOL-123456')
        ->and($cancellation->reason)->toBe('Solicitud del cliente.')
        ->and($cancellation->amountCents)->toBe(125050)
        ->and($cancellation->result)->toBe(BusTicketCancellationResult::Cancelled)
        ->and($cancellation->executedAt)->not->toBeNull()
        ->and($cancellation->auditValues()['amount_cents'])->toBe(125050);
});

test('a bus ticket cancellation requires ticket number and reason', function () {
    expect(fn () => makeBusCancellation(['ticketNumber' => '   ']))
        ->toThrow(DomainException::class, 'El número de boleto es obligatorio.');

    expect(fn () => makeBusCancellation(['reason' => '   ']))
        ->toThrow(DomainException::class, 'El motivo de la cancelación es obligatorio.');
});

test('executed cancellations require an execution date', function () {
    expect(fn () => makeBusCancellation([
        'result' => BusTicketCancellationResult::Cancelled,
        'executedAt' => null,
    ]))->toThrow(DomainException::class, 'La fecha de ejecución es obligatoria para el resultado seleccionado.');

    expect(fn () => makeBusCancellation([
        'result' => BusTicketCancellationResult::NotCancelled,
        'executedAt' => null,
    ]))->toThrow(DomainException::class, 'La fecha de ejecución es obligatoria para el resultado seleccionado.');
});

test('pending records can not have an execution date', function () {
    expect(fn () => makeBusCancellation([
        'result' => BusTicketCancellationResult::Pending,
        'executedAt' => new DateTimeImmutable('2026-10-27 10:30:00'),
    ]))->toThrow(DomainException::class, 'Un registro pendiente no puede tener fecha de ejecución.');
});

test('execution can not happen before the request', function () {
    $requestedAt = new DateTimeImmutable('2026-10-27 10:00:00');

    expect(fn () => makeBusCancellation([
        'requestedAt' => $requestedAt,
        'executedAt' => $requestedAt->modify('-1 hour'),
    ]))->toThrow(DomainException::class, 'La fecha de ejecución no puede ser anterior a la solicitud.');
});

test('amounts can not be negative', function () {
    expect(fn () => makeBusCancellation(['amountCents' => -1]))
        ->toThrow(DomainException::class, 'El importe no puede ser negativo.');
});
