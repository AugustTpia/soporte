<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Mappers;

use App\Support\Domain\Entities\TicketAttachment;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAttachmentModel;
use DateTimeImmutable;

final class TicketAttachmentMapper
{
    public function toDomain(TicketAttachmentModel $model): TicketAttachment
    {
        return new TicketAttachment(
            id: $model->id,
            companyId: $model->company_id,
            ticketId: $model->support_ticket_id,
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

    public function fillModel(TicketAttachment $attachment, TicketAttachmentModel $model): TicketAttachmentModel
    {
        $model->company_id = $attachment->companyId;
        $model->support_ticket_id = $attachment->ticketId;
        $model->uploaded_by = $attachment->uploadedBy;
        $model->original_name = $attachment->originalName;
        $model->disk = $attachment->disk;
        $model->path = $attachment->path;
        $model->mime_type = $attachment->mimeType;
        $model->size_bytes = $attachment->sizeBytes;

        return $model;
    }
}
