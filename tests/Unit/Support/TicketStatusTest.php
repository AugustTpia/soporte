<?php

use App\Support\Domain\Enums\TicketStatus;

test('ticket statuses expose their allowed transitions', function () {
    expect(TicketStatus::Open->allowedTransitions())->toBe([TicketStatus::Assigned, TicketStatus::Cancelled])
        ->and(TicketStatus::Assigned->allowedTransitions())->toBe([
            TicketStatus::Open,
            TicketStatus::InProgress,
            TicketStatus::Waiting,
            TicketStatus::Cancelled,
        ])
        ->and(TicketStatus::InProgress->allowedTransitions())->toBe([
            TicketStatus::Waiting,
            TicketStatus::Resolved,
            TicketStatus::Cancelled,
        ])
        ->and(TicketStatus::Waiting->allowedTransitions())->toBe([
            TicketStatus::InProgress,
            TicketStatus::Resolved,
            TicketStatus::Cancelled,
        ])
        ->and(TicketStatus::Resolved->allowedTransitions())->toBe([
            TicketStatus::InProgress,
            TicketStatus::Closed,
        ])
        ->and(TicketStatus::Closed->allowedTransitions())->toBe([TicketStatus::InProgress])
        ->and(TicketStatus::Cancelled->allowedTransitions())->toBe([]);
});

test('ticket statuses identify unresolved and terminal states', function () {
    expect(TicketStatus::Open->isUnresolved())->toBeTrue()
        ->and(TicketStatus::Assigned->isUnresolved())->toBeTrue()
        ->and(TicketStatus::InProgress->isUnresolved())->toBeTrue()
        ->and(TicketStatus::Waiting->isUnresolved())->toBeTrue()
        ->and(TicketStatus::Resolved->isUnresolved())->toBeFalse()
        ->and(TicketStatus::Closed->isTerminal())->toBeTrue()
        ->and(TicketStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(TicketStatus::Resolved->isTerminal())->toBeFalse();
});

test('ticket statuses requiring an assignee are explicit', function () {
    expect(TicketStatus::Assigned->requiresActiveAssignee())->toBeTrue()
        ->and(TicketStatus::InProgress->requiresActiveAssignee())->toBeTrue()
        ->and(TicketStatus::Waiting->requiresActiveAssignee())->toBeTrue()
        ->and(TicketStatus::Open->requiresActiveAssignee())->toBeFalse()
        ->and(TicketStatus::Resolved->requiresActiveAssignee())->toBeFalse();
});

test('cancelling and reopening require a reason', function () {
    expect(TicketStatus::Cancelled->requiresReason())->toBeTrue()
        ->and(TicketStatus::InProgress->requiresReason(TicketStatus::Resolved))->toBeTrue()
        ->and(TicketStatus::InProgress->requiresReason(TicketStatus::Closed))->toBeTrue()
        ->and(TicketStatus::InProgress->requiresReason(TicketStatus::Waiting))->toBeFalse()
        ->and(TicketStatus::Resolved->requiresReason(TicketStatus::InProgress))->toBeFalse();
});
