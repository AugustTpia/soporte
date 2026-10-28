<?php

namespace App\Support\Application\UseCases;

use App\Shared\Application\Contracts\AuditLogger;
use App\Support\Domain\Entities\TicketAttachment;
use App\Support\Domain\Repositories\TicketAttachmentRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAttachmentModel;

final readonly class DownloadTicketAttachment
{
    public function __construct(
        private TicketAttachmentRepository $attachments,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, int $ticketId, int $attachmentId): ?TicketAttachment
    {
        $attachment = $this->attachments->find($attachmentId, $ticketId, $companyId);

        if ($attachment === null) {
            return null;
        }

        $this->audit->log(
            event: 'ticket_attachment.downloaded',
            description: "Se descargó la evidencia {$attachment->originalName}.",
            subjectType: TicketAttachmentModel::class,
            subjectId: $attachment->id,
            newValues: $attachment->auditValues(),
            companyId: $companyId,
        );

        return $attachment;
    }
}
