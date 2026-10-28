<?php

namespace App\Maintenance\Application\UseCases;

use App\Attachments\Application\Contracts\EvidenceFileStorage;
use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Maintenance\Domain\Entities\MaintenanceExecutionAttachment;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionAttachmentRepository;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionAttachmentModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use Throwable;

final readonly class AddMaintenanceExecutionAttachment
{
    public function __construct(
        private MaintenanceExecutionRepository $executions,
        private MaintenanceExecutionAttachmentRepository $attachments,
        private EvidenceFileStorage $storage,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $executionId,
        EvidenceFileInput $file,
        int $actorId,
    ): MaintenanceExecutionAttachment {
        $execution = $this->executions->find($executionId, $companyId);

        if ($execution === null) {
            throw DomainException::because('La ejecución de mantenimiento no existe en esta empresa.');
        }

        if (! $execution->status->isEditable()) {
            throw DomainException::because('No se pueden adjuntar evidencias a una ejecución cerrada.');
        }

        $stored = $this->storage->store(
            directory: sprintf('companies/%d/maintenance-executions/%d', $companyId, $executionId),
            file: $file,
        );

        try {
            return $this->transactions->run(function () use (
                $companyId,
                $executionId,
                $execution,
                $file,
                $actorId,
                $stored,
            ): MaintenanceExecutionAttachment {
                $saved = $this->attachments->save(new MaintenanceExecutionAttachment(
                    id: null,
                    companyId: $companyId,
                    executionId: $executionId,
                    originalName: $file->originalName,
                    disk: $stored->disk,
                    path: $stored->path,
                    mimeType: $file->mimeType,
                    sizeBytes: $file->sizeBytes,
                    uploadedBy: $actorId,
                ));

                $this->audit->log(
                    event: 'maintenance_execution_attachment.uploaded',
                    description: "Se adjuntó una evidencia al mantenimiento {$execution->targetLabel}.",
                    subjectType: MaintenanceExecutionAttachmentModel::class,
                    subjectId: $saved->id,
                    newValues: $saved->auditValues(),
                    companyId: $companyId,
                );

                return $saved;
            });
        } catch (Throwable $exception) {
            $this->storage->delete($stored->disk, $stored->path);

            throw $exception;
        }
    }
}
