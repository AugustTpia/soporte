<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;

final readonly class SummarizeBusTicketCancellations
{
    public function __construct(private BusTicketCancellationRepository $cancellations) {}

    /**
     * @return array{
     *     total: int,
     *     cancelled: int,
     *     not_cancelled: int,
     *     pending: int,
     *     total_amount_cents: int
     * }
     */
    public function execute(int $companyId, BusTicketCancellationFilters $filters): array
    {
        return $this->cancellations->summary($companyId, $filters);
    }
}
