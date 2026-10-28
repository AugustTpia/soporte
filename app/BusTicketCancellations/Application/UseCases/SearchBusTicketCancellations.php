<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchBusTicketCancellations
{
    public function __construct(private BusTicketCancellationRepository $cancellations) {}

    /**
     * @return Page<BusTicketCancellation>
     */
    public function execute(
        int $companyId,
        BusTicketCancellationFilters $filters,
        int $page = 1,
        int $perPage = 20,
    ): Page {
        return $this->cancellations->search($companyId, $filters, $page, $perPage);
    }
}
