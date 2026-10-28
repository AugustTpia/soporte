<?php

namespace App\Support\Application\UseCases;

use App\Support\Domain\Repositories\TicketRepository;

final readonly class CountDashboardTickets
{
    public function __construct(private TicketRepository $tickets) {}

    /**
     * @return array{unresolved: int, unassigned: int, mine: int, urgent: int}
     */
    public function execute(int $companyId, int $userId): array
    {
        return $this->tickets->dashboardCounts($companyId, $userId);
    }
}
