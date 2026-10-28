<?php

namespace App\Support\Domain\Repositories;

use App\Shared\Domain\Pagination\Page;
use App\Support\Application\DTOs\TicketFilters;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\TicketActivityType;

interface TicketRepository
{
    /**
     * @return Page<SupportTicket>
     */
    public function search(int $companyId, TicketFilters $filters, int $page = 1, int $perPage = 15): Page;

    public function find(int $id, int $companyId): ?SupportTicket;

    /**
     * @return array<int, SupportTicket>
     */
    public function forCompany(int $companyId): array;

    public function save(SupportTicket $ticket): SupportTicket;

    public function addAssignment(int $ticketId, int $userId, ?int $assignedBy, ?string $reason = null): void;

    public function closeAssignment(int $ticketId, int $userId, ?int $actorId, ?string $reason = null): void;

    public function findActiveAssignment(int $ticketId, int $userId): ?TicketAssignment;

    public function activeAssignmentCount(int $ticketId): int;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordActivity(
        int $ticketId,
        ?int $actorId,
        TicketActivityType $type,
        ?string $body = null,
        array $metadata = [],
    ): void;

    /**
     * @return array{unresolved: int, unassigned: int, mine: int, urgent: int}
     */
    public function dashboardCounts(int $companyId, int $userId): array;
}
