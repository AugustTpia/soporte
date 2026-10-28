<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Repositories;

use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Entities\TicketAttachment;
use App\Support\Domain\Repositories\TicketAttachmentRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Mappers\TicketAttachmentMapper;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAttachmentModel;

final class EloquentTicketAttachmentRepository implements TicketAttachmentRepository
{
    public function __construct(private readonly TicketAttachmentMapper $mapper) {}

    /**
     * @return array<int, TicketAttachment>
     */
    public function forTicket(int $ticketId, int $companyId): array
    {
        return TicketAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('support_ticket_id', $ticketId)
            ->orderBy('id')
            ->get()
            ->map(fn (TicketAttachmentModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function find(int $id, int $ticketId, int $companyId): ?TicketAttachment
    {
        $model = TicketAttachmentModel::query()
            ->with('uploader')
            ->where('company_id', $companyId)
            ->where('support_ticket_id', $ticketId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(TicketAttachment $attachment): TicketAttachment
    {
        $model = $attachment->id === null
            ? new TicketAttachmentModel
            : TicketAttachmentModel::query()
                ->where('company_id', $attachment->companyId)
                ->where('support_ticket_id', $attachment->ticketId)
                ->findOrFail($attachment->id);

        $this->mapper->fillModel($attachment, $model)->save();

        return $this->find($model->id, $model->support_ticket_id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar el archivo adjunto guardado.');
    }
}
