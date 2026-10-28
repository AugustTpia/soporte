<?php

namespace App\BusTicketCancellations\Application\UseCases;

use App\Attachments\Application\Contracts\EvidenceFileStorage;
use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\BusTicketCancellations\Domain\Entities\BusTicketCancellationAttachment;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationAttachmentModel;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use Throwable;

final readonly class AddBusTicketCancellationAttachment
{
    public function __construct(
        private BusTicketCancellationRepository $cancellations,
        private BusTicketCancellationAttachmentRepository $attachments,
        private CompanyRepository $companies,
        private EvidenceFileStorage $storage,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $cancellationId,
        EvidenceFileInput $file,
        int $actorId,
    ): BusTicketCancellationAttachment {
        $company = $this->companies->find($companyId);

        if ($company === null || ! $company->hasFeature(CompanyFeature::BusTicketCancellations)) {
            throw DomainException::because('El módulo de cancelación de boletos no está habilitado para esta empresa.');
        }

        $cancellation = $this->cancellations->find($cancellationId, $companyId);

        if ($cancellation === null) {
            throw DomainException::because('La cancelación seleccionada no existe en esta empresa.');
        }

        $stored = $this->storage->store(
            directory: sprintf('companies/%d/bus-ticket-cancellations/%d', $companyId, $cancellationId),
            file: $file,
        );

        try {
            return $this->transactions->run(function () use (
                $companyId,
                $cancellationId,
                $cancellation,
                $file,
                $actorId,
                $stored,
            ): BusTicketCancellationAttachment {
                $saved = $this->attachments->save(new BusTicketCancellationAttachment(
                    id: null,
                    companyId: $companyId,
                    cancellationId: $cancellationId,
                    originalName: $file->originalName,
                    disk: $stored->disk,
                    path: $stored->path,
                    mimeType: $file->mimeType,
                    sizeBytes: $file->sizeBytes,
                    uploadedBy: $actorId,
                ));

                $this->audit->log(
                    event: 'bus_ticket_cancellation_attachment.uploaded',
                    description: "Se adjuntó una evidencia a la cancelación del boleto {$cancellation->ticketNumber}.",
                    subjectType: BusTicketCancellationAttachmentModel::class,
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
