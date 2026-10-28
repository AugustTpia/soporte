<?php

namespace App\BusTicketCancellations\Domain\Repositories;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Domain\Entities\BusTicketCancellation;
use App\Shared\Domain\Pagination\Page;

interface BusTicketCancellationRepository
{
    /**
     * @return Page<BusTicketCancellation>
     */
    public function search(
        int $companyId,
        BusTicketCancellationFilters $filters,
        int $page = 1,
        int $perPage = 20,
    ): Page;

    public function find(int $id, int $companyId): ?BusTicketCancellation;

    public function save(BusTicketCancellation $cancellation): BusTicketCancellation;

    /**
     * @return array{
     *     total: int,
     *     cancelled: int,
     *     not_cancelled: int,
     *     pending: int,
     *     total_amount_cents: int
     * }
     */
    public function summary(int $companyId, BusTicketCancellationFilters $filters): array;

    /**
     * @return iterable<int, BusTicketCancellation>
     */
    public function export(int $companyId, BusTicketCancellationFilters $filters): iterable;
}
