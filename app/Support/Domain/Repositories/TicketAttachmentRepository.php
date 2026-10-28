<?php

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketAttachment;

interface TicketAttachmentRepository
{
    /**
     * @return array<int, TicketAttachment>
     */
    public function forTicket(int $ticketId, int $companyId): array;

    public function find(int $id, int $ticketId, int $companyId): ?TicketAttachment;

    public function save(TicketAttachment $attachment): TicketAttachment;
}
