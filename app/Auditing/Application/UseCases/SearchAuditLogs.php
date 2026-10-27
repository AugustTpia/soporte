<?php

namespace App\Auditing\Application\UseCases;

use App\Auditing\Domain\Entities\AuditEntry;
use App\Auditing\Domain\Repositories\AuditRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchAuditLogs
{
    public function __construct(private AuditRepository $audit) {}

    /**
     * @return Page<AuditEntry>
     */
    public function execute(
        ?int $companyId,
        ?string $term,
        ?string $event,
        ?string $from,
        ?string $to,
        int $page = 1,
        int $perPage = 20,
    ): Page {
        return $this->audit->search($companyId, $term, $event, $from, $to, $page, $perPage);
    }
}
