<?php

namespace App\Contacts\Infrastructure\Persistence\Eloquent\Repositories;

use App\Contacts\Domain\Entities\ClientContact;
use App\Contacts\Domain\Repositories\ClientContactRepository;
use App\Contacts\Infrastructure\Persistence\Eloquent\Mappers\ClientContactMapper;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;

final class EloquentClientContactRepository implements ClientContactRepository
{
    public function __construct(private readonly ClientContactMapper $mapper) {}

    /**
     * @return array<int, ClientContact>
     */
    public function forCompany(int $companyId, bool $activeOnly = false, ?int $includeId = null): array
    {
        return ClientContactModel::query()
            ->with('location')
            ->where('company_id', $companyId)
            ->when($activeOnly, function ($query) use ($includeId): void {
                $query->where(function ($query) use ($includeId): void {
                    $query->where('is_active', true);

                    if ($includeId !== null) {
                        $query->orWhereKey($includeId);
                    }
                });
            })
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn (ClientContactModel $model) => $this->mapper->toDomain($model))
            ->all();
    }

    public function find(int $id, int $companyId): ?ClientContact
    {
        $model = ClientContactModel::query()
            ->with('location')
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(ClientContact $contact): ClientContact
    {
        $model = $contact->id === null
            ? new ClientContactModel
            : ClientContactModel::query()
                ->where('company_id', $contact->companyId)
                ->findOrFail($contact->id);

        $this->mapper->fillModel($contact, $model)->save();

        return $this->mapper->toDomain($model->refresh()->load('location'));
    }

    public function clearPrimary(int $companyId, ?int $ignoreId = null): void
    {
        ClientContactModel::query()
            ->where('company_id', $companyId)
            ->where('is_primary', true)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->update(['is_primary' => false]);
    }

    public function countActive(int $companyId): int
    {
        return ClientContactModel::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->count();
    }
}
