<?php

namespace App\Auditing\Domain\Repositories;

use App\Auditing\Domain\Entities\AuditEntry;
use App\Shared\Domain\Pagination\Page;

interface AuditRepository
{
    /**
     * @return Page<AuditEntry>
     */
    public function search(
        ?int $companyId,
        ?string $term,
        ?string $event,
        ?string $from,
        ?string $to,
        int $page = 1,
        int $perPage = 20,
    ): Page;

    public function count(?int $companyId): int;
}
