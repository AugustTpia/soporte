<?php

namespace App\Maintenance\Domain\Repositories;

use App\Maintenance\Domain\Entities\MaintenanceExecutionAttachment;

interface MaintenanceExecutionAttachmentRepository
{
    /**
     * @return array<int, MaintenanceExecutionAttachment>
     */
    public function forExecution(int $executionId, int $companyId): array;

    public function find(int $id, int $executionId, int $companyId): ?MaintenanceExecutionAttachment;

    public function save(MaintenanceExecutionAttachment $attachment): MaintenanceExecutionAttachment;
}
