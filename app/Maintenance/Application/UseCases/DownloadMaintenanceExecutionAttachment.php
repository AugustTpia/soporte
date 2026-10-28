<?php

namespace App\Maintenance\Application\UseCases;

use App\Maintenance\Domain\Entities\MaintenanceExecutionAttachment;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionAttachmentRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionAttachmentModel;
use App\Shared\Application\Contracts\AuditLogger;

final readonly class DownloadMaintenanceExecutionAttachment
{
    public function __construct(
        private MaintenanceExecutionAttachmentRepository $attachments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $executionId, int $attachmentId): ?MaintenanceExecutionAttachment
    {
        $attachment = $this->attachments->find($attachmentId, $executionId, $companyId);

        if ($attachment === null) {
            return null;
        }

        $this->audit->log(
            event: 'maintenance_execution_attachment.downloaded',
            description: "Se descargó la evidencia {$attachment->originalName}.",
            subjectType: MaintenanceExecutionAttachmentModel::class,
            subjectId: $attachment->id,
            newValues: $attachment->auditValues(),
            companyId: $companyId,
        );

        return $attachment;
    }
}
