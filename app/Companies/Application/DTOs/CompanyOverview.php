<?php

namespace App\Companies\Application\DTOs;

final readonly class CompanyOverview
{
    /**
     * @param  array<string, int>  $counts
     * @param  array<int, array{id: int, folio: string|null, subject: string, status: string, opened_at: string}>  $recentTickets
     * @param  array<int, array{id: int, title: string, target: string, status: string, scheduled_at: string}>  $recentMaintenance
     * @param  array<int, array{id: int, ticket_number: string, result: string, requested_at: string}>  $recentCancellations
     * @param  array<int, array{event: string, description: string, actor: string|null, created_at: string}>  $recentActivity
     */
    public function __construct(
        public array $counts,
        public array $recentTickets,
        public array $recentMaintenance,
        public array $recentCancellations,
        public array $recentActivity,
    ) {}
}
