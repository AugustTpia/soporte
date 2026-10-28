<?php

namespace App\BusTicketCancellations\Domain\Repositories;

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellationAttachment;

interface BusTicketCancellationAttachmentRepository
{
    /**
     * @return array<int, BusTicketCancellationAttachment>
     */
    public function forCancellation(int $cancellationId, int $companyId): array;

    public function find(int $id, int $cancellationId, int $companyId): ?BusTicketCancellationAttachment;

    public function save(BusTicketCancellationAttachment $attachment): BusTicketCancellationAttachment;
}
