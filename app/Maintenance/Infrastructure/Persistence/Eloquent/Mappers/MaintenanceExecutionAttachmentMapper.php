<?php

namespace App\Maintenance\Infrastructure\Persistence\Eloquent\Mappers;

use App\Maintenance\Domain\Entities\MaintenanceExecutionAttachment;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionAttachmentModel;
use DateTimeImmutable;

final class MaintenanceExecutionAttachmentMapper
{
    public function toDomain(MaintenanceExecutionAttachmentModel $model): MaintenanceExecutionAttachment
    {
        return new MaintenanceExecutionAttachment(
            id: $model->id,
            companyId: $model->company_id,
            executionId: $model->maintenance_execution_id,
            originalName: $model->original_name,
            disk: $model->disk,
            path: $model->path,
            mimeType: $model->mime_type,
            sizeBytes: $model->size_bytes,
            uploadedBy: $model->uploaded_by,
            createdAt: $model->created_at !== null
                ? DateTimeImmutable::createFromInterface($model->created_at)
                : null,
            uploadedByName: $model->relationLoaded('uploader') ? $model->uploader?->name : null,
        );
    }

    public function fillModel(
        MaintenanceExecutionAttachment $attachment,
        MaintenanceExecutionAttachmentModel $model,
    ): MaintenanceExecutionAttachmentModel {
        $model->company_id = $attachment->companyId;
        $model->maintenance_execution_id = $attachment->executionId;
        $model->uploaded_by = $attachment->uploadedBy;
        $model->original_name = $attachment->originalName;
        $model->disk = $attachment->disk;
        $model->path = $attachment->path;
        $model->mime_type = $attachment->mimeType;
        $model->size_bytes = $attachment->sizeBytes;

        return $model;
    }
}
