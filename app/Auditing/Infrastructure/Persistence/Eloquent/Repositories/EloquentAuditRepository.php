<?php

namespace App\Auditing\Infrastructure\Persistence\Eloquent\Repositories;

use App\Auditing\Domain\Entities\AuditEntry;
use App\Auditing\Domain\Repositories\AuditRepository;
use App\Auditing\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentAuditRepository implements AuditRepository
{
    /**
     * @return Page<AuditEntry>
     */
    public function search(
        ?int $companyId,
        ?string $term,
        ?string $event,
        ?string $from,
        ?string $to,
        int $page = 1,
        int $perPage = 20,
    ): Page {
        $query = AuditLogModel::query()
            ->with(['actor', 'company'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('description', 'like', "%{$term}%")
                        ->orWhere('event', 'like', "%{$term}%")
                        ->orWhereHas('actor', function ($query) use ($term): void {
                            $query->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                });
            })
            ->when(filled($event), fn ($query) => $query->where('event', $event))
            ->when(filled($from), fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when(filled($to), fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (AuditLogModel $model) => $this->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function count(?int $companyId): int
    {
        return AuditLogModel::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->count();
    }

    private function toDomain(AuditLogModel $model): AuditEntry
    {
        return new AuditEntry(
            id: $model->id,
            companyId: $model->company_id,
            companyName: $model->company?->name,
            actorId: $model->actor_id,
            actorName: $model->actor?->name,
            event: $model->event,
            description: $model->description,
            subjectType: $model->subject_type,
            subjectId: $model->subject_id,
            oldValues: $model->old_values,
            newValues: $model->new_values,
            metadata: $model->metadata,
            ipAddress: $model->ip_address,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
