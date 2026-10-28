<?php

namespace App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Repositories;

use App\BusTicketCancellations\Domain\Entities\BusTicketCancellationAttachment;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Mappers\BusTicketCancellationAttachmentMapper;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationAttachmentModel;
use App\Shared\Domain\Exceptions\DomainException;

final class EloquentBusTicketCancellationAttachmentRepository implements BusTicketCancellationAttachmentRepository
{
    public function __construct(private readonly BusTicketCancellationAttachmentMapper $mapper) {}

    /**
     * @return array<int, BusTicketCancellationAttachment>
     */
    public function forCancellation(int $cancellationId, int $companyId): array
    {
        return BusTicketCancellationAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('bus_ticket_cancellation_id', $cancellationId)
            ->orderBy('id')
            ->get()
            ->map(fn (BusTicketCancellationAttachmentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function find(int $id, int $cancellationId, int $companyId): ?BusTicketCancellationAttachment
    {
        $model = BusTicketCancellationAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('bus_ticket_cancellation_id', $cancellationId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(BusTicketCancellationAttachment $attachment): BusTicketCancellationAttachment
    {
        $model = $attachment->id === null
            ? new BusTicketCancellationAttachmentModel
            : BusTicketCancellationAttachmentModel::query()
                ->where('company_id', $attachment->companyId)
                ->where('bus_ticket_cancellation_id', $attachment->cancellationId)
                ->findOrFail($attachment->id);

        $this->mapper->fillModel($attachment, $model)->save();

        return $this->find($model->id, $model->bus_ticket_cancellation_id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar la evidencia guardada.');
    }
}
