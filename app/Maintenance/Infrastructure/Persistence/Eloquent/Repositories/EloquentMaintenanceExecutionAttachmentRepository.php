<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use App\Maintenance\Domain\Entities\MaintenanceExecutionAttachment;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionAttachmentRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers\MaintenanceExecutionAttachmentMapper;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionAttachmentModel;
use App\Shared\Domain\Exceptions\DomainException;

final class EloquentMaintenanceExecutionAttachmentRepository implements MaintenanceExecutionAttachmentRepository
{
    public function __construct(private readonly MaintenanceExecutionAttachmentMapper $mapper) {}

    /**
     * @return array<int, MaintenanceExecutionAttachment>
     */
    public function forExecution(int $executionId, int $companyId): array
    {
        return MaintenanceExecutionAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('maintenance_execution_id', $executionId)
            ->orderBy('id')
            ->get()
            ->map(fn (MaintenanceExecutionAttachmentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function find(int $id, int $executionId, int $companyId): ?MaintenanceExecutionAttachment
    {
        $model = MaintenanceExecutionAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('maintenance_execution_id', $executionId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(MaintenanceExecutionAttachment $attachment): MaintenanceExecutionAttachment
    {
        $model = $attachment->id === null
            ? new MaintenanceExecutionAttachmentModel
            : MaintenanceExecutionAttachmentModel::query()
                ->where('company_id', $attachment->companyId)
                ->where('maintenance_execution_id', $attachment->executionId)
                ->findOrFail($attachment->id);

        $this->mapper->fillModel($attachment, $model)->save();

        return $this->find($model->id, $model->maintenance_execution_id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar el adjunto guardado.');
    }
}
