<?php

namespace App\Support\Infrastructure\Persistence\Eloquent\Repositories;

use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Pagination\Page;
use App\Support\Application\DTOs\TicketFilters;
use App\Support\Domain\Entities\SupportTicket;
use App\Support\Domain\Entities\TicketAssignment;
use App\Support\Domain\Enums\TicketActivityType;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Domain\Repositories\TicketRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Mappers\SupportTicketMapper;
use App\Support\Infrastructure\Persistence\Eloquent\Mappers\TicketAssignmentMapper;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketActivityModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\TicketAssignmentModel;

final class EloquentTicketRepository implements TicketRepository
{
    public function __construct(
        private readonly SupportTicketMapper $mapper,
        private readonly TicketAssignmentMapper $assignmentMapper,
    ) {}

    /**
     * @return Page<SupportTicket>
     */
    public function search(int $companyId, TicketFilters $filters, int $page = 1, int $perPage = 15): Page
    {
        $query = SupportTicketModel::query()
            ->with(['location', 'asset', 'serviceAccount', 'clientContact', 'creator', 'assignments.user'])
            ->where('company_id', $companyId)
            ->when(filled($filters->term), function ($query) use ($filters): void {
                $term = $filters->term;

                $query->where(function ($query) use ($term): void {
                    $query->where('folio', 'like', "%{$term}%")
                        ->orWhere('subject', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('requester_name', 'like', "%{$term}%")
                        ->orWhere('requester_contact', 'like', "%{$term}%");
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status->value))
            ->when($filters->priority !== null, fn ($query) => $query->where('priority', $filters->priority->value))
            ->when($filters->category !== null, fn ($query) => $query->where('category', $filters->category->value))
            ->when($filters->unresolved, fn ($query) => $query->whereIn('status', $this->unresolvedStatuses()))
            ->when($filters->assignedTo !== null, function ($query) use ($filters): void {
                $query->whereHas('activeAssignments', fn ($query) => $query->where('user_id', $filters->assignedTo));
            })
            ->when($filters->unassigned, fn ($query) => $query->whereDoesntHave('activeAssignments'))
            ->when($filters->from !== null, fn ($query) => $query->whereDate('opened_at', '>=', $filters->from->format('Y-m-d')))
            ->when($filters->to !== null, fn ($query) => $query->whereDate('opened_at', '<=', $filters->to->format('Y-m-d')))
            ->orderByDesc('opened_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (SupportTicketModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?SupportTicket
    {
        $model = SupportTicketModel::query()
            ->with([
                'location',
                'asset',
                'serviceAccount',
                'clientContact',
                'creator',
                'assignments.user',
                'activities.actor',
            ])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function forCompany(int $companyId): array
    {
        return SupportTicketModel::query()
            ->with(['location', 'asset', 'serviceAccount', 'clientContact', 'creator'])
            ->where('company_id', $companyId)
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (SupportTicketModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function save(SupportTicket $ticket): SupportTicket
    {
        $model = $ticket->id === null
            ? new SupportTicketModel
            : SupportTicketModel::query()
                ->where('company_id', $ticket->companyId)
                ->findOrFail($ticket->id);

        $this->mapper->fillModel($ticket, $model)->save();

        if ($model->folio === null) {
            $model->folio = 'TCK-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT);
            $model->save();
        }

        return $this->find($model->id, $model->company_id)
            ?? throw DomainException::because('No se pudo recuperar el ticket guardado.');
    }

    public function addAssignment(int $ticketId, int $userId, ?int $assignedBy, ?string $reason = null): void
    {
        TicketAssignmentModel::query()->create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
            'reason' => $reason,
        ]);
    }

    public function closeAssignment(int $ticketId, int $userId, ?int $actorId, ?string $reason = null): void
    {
        TicketAssignmentModel::query()
            ->where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->whereNull('unassigned_at')
            ->update([
                'unassigned_by' => $actorId,
                'unassigned_at' => now(),
                'reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function findActiveAssignment(int $ticketId, int $userId): ?TicketAssignment
    {
        $model = TicketAssignmentModel::query()
            ->with('user')
            ->where('ticket_id', $ticketId)
            ->where('user_id', $userId)
            ->whereNull('unassigned_at')
            ->first();

        return $model ? $this->assignmentMapper->toDomain($model) : null;
    }

    public function activeAssignmentCount(int $ticketId): int
    {
        return TicketAssignmentModel::query()
            ->where('ticket_id', $ticketId)
            ->whereNull('unassigned_at')
            ->count();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function recordActivity(
        int $ticketId,
        ?int $actorId,
        TicketActivityType $type,
        ?string $body = null,
        array $metadata = [],
    ): void {
        TicketActivityModel::query()->create([
            'ticket_id' => $ticketId,
            'actor_id' => $actorId,
            'type' => $type->value,
            'body' => $body,
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{unresolved: int, unassigned: int, mine: int, urgent: int}
     */
    public function dashboardCounts(int $companyId, int $userId): array
    {
        $base = SupportTicketModel::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $this->unresolvedStatuses());

        return [
            'unresolved' => (clone $base)->count(),
            'unassigned' => (clone $base)->whereDoesntHave('activeAssignments')->count(),
            'mine' => (clone $base)->whereHas('activeAssignments', fn ($query) => $query->where('user_id', $userId))->count(),
            'urgent' => (clone $base)->where('priority', 'urgent')->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function unresolvedStatuses(): array
    {
        return [
            TicketStatus::Open->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::Waiting->value,
        ];
    }
}
