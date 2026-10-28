<?php

use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;

function makeDomainTicket(?DateTimeImmutable $openedAt = null): SupportTicket
{
    return SupportTicket::create(
        companyId: 1,
        category: TicketCategory::Hardware,
        subject: '  Equipo sin acceso  ',
        description: '  No enciende desde esta mañana.  ',
        priority: TicketPriority::High,
        requesterName: '  Juan Pérez  ',
        openedAt: $openedAt ?? new DateTimeImmutable,
        createdBy: 5,
    );
}

function activeAssignment(int $userId = 10): TicketAssignment
{
    return new TicketAssignment(
        id: 1,
        ticketId: 1,
        userId: $userId,
        userName: 'Técnico',
        assignedAt: new DateTimeImmutable,
    );
}

test('a ticket trims its text fields and starts open', function () {
    $ticket = makeDomainTicket();

    expect($ticket->subject)->toBe('Equipo sin acceso')
        ->and($ticket->description)->toBe('No enciende desde esta mañana.')
        ->and($ticket->requesterName)->toBe('Juan Pérez')
        ->and($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->folio)->toBeNull()
        ->and($ticket->activeAssignmentCount())->toBe(0);
});

test('a ticket requires subject and description', function () {
    expect(fn () => SupportTicket::create(
        companyId: 1,
        category: TicketCategory::Hardware,
        subject: '   ',
        description: 'Descripción',
        priority: TicketPriority::Medium,
    ))->toThrow(DomainException::class, 'El asunto del ticket es obligatorio.');

    expect(fn () => SupportTicket::create(
        companyId: 1,
        category: TicketCategory::Hardware,
        subject: 'Asunto',
        description: '   ',
        priority: TicketPriority::Medium,
    ))->toThrow(DomainException::class, 'La descripción del ticket es obligatoria.');
});

test('a due date can not be earlier than the ticket opening date', function () {
    $openedAt = new DateTimeImmutable('2026-10-27 10:00:00');

    expect(fn () => SupportTicket::create(
        companyId: 1,
        category: TicketCategory::Hardware,
        subject: 'Asunto',
        description: 'Descripción',
        priority: TicketPriority::Medium,
        dueAt: $openedAt->modify('-1 hour'),
        openedAt: $openedAt,
    ))->toThrow(DomainException::class, 'La fecha compromiso no puede ser anterior a la apertura del ticket.');
});

test('cancelling a ticket requires and stores a reason', function () {
    $ticket = makeDomainTicket();

    expect(fn () => $ticket->changeStatus(TicketStatus::Cancelled))
        ->toThrow(DomainException::class, 'El motivo es obligatorio para cancelar o reabrir un ticket.');

    $ticket->changeStatus(TicketStatus::Cancelled, 'Solicitud duplicada.');

    expect($ticket->status)->toBe(TicketStatus::Cancelled)
        ->and($ticket->cancellationReason)->toBe('Solicitud duplicada.')
        ->and($ticket->cancelledAt)->not->toBeNull();
});

test('a ticket can not enter progress without an active technician', function () {
    $ticket = makeDomainTicket();
    $ticket->status = TicketStatus::Assigned;

    expect(fn () => $ticket->changeStatus(TicketStatus::InProgress))
        ->toThrow(DomainException::class, 'El ticket necesita al menos un técnico asignado para ese estado.');
});

test('reopening a resolved ticket requires a reason and clears resolution data', function () {
    $ticket = makeDomainTicket();
    $ticket->id = 1;
    $ticket->status = TicketStatus::InProgress;
    $ticket->assignments = [activeAssignment()];

    $ticket->changeStatus(TicketStatus::Resolved);

    expect($ticket->status)->toBe(TicketStatus::Resolved)
        ->and($ticket->resolvedAt)->not->toBeNull();

    expect(fn () => $ticket->changeStatus(TicketStatus::InProgress))
        ->toThrow(DomainException::class, 'El motivo es obligatorio para cancelar o reabrir un ticket.');

    $ticket->changeStatus(TicketStatus::InProgress, 'El cliente reportó que el problema continúa.');

    expect($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->resolvedAt)->toBeNull()
        ->and($ticket->closedAt)->toBeNull();
});

test('the last technician can not be removed from an in-progress ticket', function () {
    $ticket = makeDomainTicket();
    $ticket->id = 1;
    $ticket->status = TicketStatus::InProgress;
    $ticket->assignments = [activeAssignment(10)];

    expect(fn () => $ticket->assertCanRemoveAssignee(10))
        ->toThrow(DomainException::class, 'No puedes retirar al último técnico de un ticket en progreso o en espera.');

    $ticket->assignments[] = activeAssignment(11);

    $ticket->assertCanRemoveAssignee(10);

    expect($ticket->activeAssignmentCount())->toBe(2)
        ->and($ticket->isAssignedTo(10))->toBeTrue()
        ->and($ticket->isAssignedTo(11))->toBeTrue();
});

test('only valid folios are accepted', function () {
    $ticket = makeDomainTicket();

    $ticket->setFolio('TCK-000123');
    expect($ticket->folio)->toBe('TCK-000123');

    expect(fn () => $ticket->setFolio('12345'))
        ->toThrow(DomainException::class, 'El folio del ticket no tiene un formato válido.');
});

test('a cancelled ticket can not be edited', function () {
    $ticket = makeDomainTicket();
    $ticket->status = TicketStatus::Cancelled;

    expect(fn () => $ticket->updateDetails(
        category: TicketCategory::Connectivity,
        subject: 'Nuevo asunto',
        description: 'Nueva descripción',
        requesterName: null,
        requesterContact: null,
        priority: TicketPriority::Low,
        locationId: null,
        assetId: null,
        serviceAccountId: null,
        dueAt: null,
    ))->toThrow(DomainException::class, 'No se puede editar un ticket cancelado.');
});
