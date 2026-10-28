<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellationAttachment;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationAttachmentModel;
use App\Shared\Application\Contracts\AuditLogger;

final readonly class DownloadBusTicketCancellationAttachment
{
    public function __construct(
        private BusTicketCancellationAttachmentRepository $attachments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $cancellationId, int $attachmentId): ?BusTicketCancellationAttachment
    {
        $attachment = $this->attachments->find($attachmentId, $cancellationId, $companyId);

        if ($attachment === null) {
            return null;
        }

        $this->audit->log(
            event: 'bus_ticket_cancellation_attachment.downloaded',
            description: "Se descargó la evidencia {$attachment->originalName}.",
            subjectType: BusTicketCancellationAttachmentModel::class,
            subjectId: $attachment->id,
            newValues: $attachment->auditValues(),
            companyId: $companyId,
        );

        return $attachment;
    }
}
