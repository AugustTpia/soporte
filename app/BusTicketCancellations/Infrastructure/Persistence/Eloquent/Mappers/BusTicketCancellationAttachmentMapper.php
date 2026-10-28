<?php

namespace App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Mappers;

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellationAttachment;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationAttachmentModel;
use DateTimeImmutable;

final class BusTicketCancellationAttachmentMapper
{
    public function toDomain(BusTicketCancellationAttachmentModel $model): BusTicketCancellationAttachment
    {
        return new BusTicketCancellationAttachment(
            id: $model->id,
            companyId: $model->company_id,
            cancellationId: $model->bus_ticket_cancellation_id,
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
        BusTicketCancellationAttachment $attachment,
        BusTicketCancellationAttachmentModel $model,
    ): BusTicketCancellationAttachmentModel {
        $model->company_id = $attachment->companyId;
        $model->bus_ticket_cancellation_id = $attachment->cancellationId;
        $model->uploaded_by = $attachment->uploadedBy;
        $model->original_name = $attachment->originalName;
        $model->disk = $attachment->disk;
        $model->path = $attachment->path;
        $model->mime_type = $attachment->mimeType;
        $model->size_bytes = $attachment->sizeBytes;

        return $model;
    }
}
