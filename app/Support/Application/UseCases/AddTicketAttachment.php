<?php

namespace App\Support\Application\UseCases;

use App\Attachments\Application\Contracts\EvidenceFileStorage;
use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Entities\TicketAttachment;
use App\Support\Domain\Repositories\TicketAttachmentRepository;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAttachmentModel;
use Throwable;

final readonly class AddTicketAttachment
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketAttachmentRepository $attachments,
        private EvidenceFileStorage $storage,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $ticketId,
        EvidenceFileInput $file,
        int $actorId,
    ): TicketAttachment {
        $ticket = $this->tickets->find($ticketId, $companyId);

        if ($ticket === null) {
            throw DomainException::because('El ticket seleccionado no existe en esta empresa.');
        }

        $stored = $this->storage->store(
            directory: sprintf('companies/%d/tickets/%d', $companyId, $ticketId),
            file: $file,
        );

        try {
            return $this->transactions->run(function () use (
                $companyId,
                $ticketId,
                $ticket,
                $file,
                $actorId,
                $stored,
            ): TicketAttachment {
                $saved = $this->attachments->save(new TicketAttachment(
                    id: null,
                    companyId: $companyId,
                    ticketId: $ticketId,
                    originalName: $file->originalName,
                    disk: $stored->disk,
                    path: $stored->path,
                    mimeType: $file->mimeType,
                    sizeBytes: $file->sizeBytes,
                    uploadedBy: $actorId,
                ));

                $this->audit->log(
                    event: 'ticket_attachment.uploaded',
                    description: 'Se adjuntó una evidencia al ticket '.($ticket->folio ?? "#{$ticketId}").'.',
                    subjectType: TicketAttachmentModel::class,
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
