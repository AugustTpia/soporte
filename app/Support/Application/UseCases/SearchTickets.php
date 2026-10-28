<?php

namespace App\Support\Application\UseCases;

use App\Shared\Domain\Pagination\Page;
use App\Support\Application\DTOs\TicketFilters;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Repositories\TicketRepository;

final readonly class SearchTickets
{
    public function __construct(private TicketRepository $tickets) {}

    /**
     * @return Page<SupportTicket>
     */
    public function execute(int $companyId, TicketFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        return $this->tickets->search($companyId, $filters, $page, $perPage);
    }
}
