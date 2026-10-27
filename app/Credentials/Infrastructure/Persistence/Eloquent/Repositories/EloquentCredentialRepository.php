<?php

namespace App\Credentials\Infrastructure\Persistence\Eloquent\Repositories;

use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Mappers\CredentialMapper;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialSecretModel;
use App\Shared\Domain\Pagination\Page;

final class EloquentCredentialRepository implements CredentialRepository
{
    public function __construct(private readonly CredentialMapper $mapper) {}

    /**
     * @return Page<Credential>
     */
    public function search(
        int $companyId,
        ?string $term,
        int $page = 1,
        int $perPage = 15,
        ?int $serviceAccountId = null,
        ?CredentialStatus $status = null,
        ?CredentialKind $kind = null,
    ): Page {
        $query = CredentialModel::query()
            ->with([
                'account' => fn ($query) => $query->withTrashed(),
                'secret',
            ])
            ->where('company_id', $companyId)
            ->when(filled($term), function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('label', 'like', "%{$term}%")
                        ->orWhereHas('account', function ($query) use ($term): void {
                            $query->withTrashed()
                                ->where('username', 'like', "%{$term}%")
                                ->orWhere('instance', 'like', "%{$term}%")
                                ->orWhere('owner_name', 'like', "%{$term}%");
                        });
                });
            })
            ->when($serviceAccountId !== null, fn ($query) => $query->where('service_account_id', $serviceAccountId))
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($kind !== null, fn ($query) => $query->where('kind', $kind->value))
            ->orderBy('label');

        $paginator = $query->paginate($perPage, page: max(1, $page));

        return new Page(
            items: $paginator->getCollection()->map(fn (CredentialModel $model) => $this->mapper->toDomain($model))->all(),
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
        );
    }

    public function find(int $id, int $companyId): ?Credential
    {
        $model = CredentialModel::query()
            ->with([
                'account' => fn ($query) => $query->withTrashed(),
                'secret',
            ])
            ->where('company_id', $companyId)
            ->find($id);

        return $model ? $this->mapper->toDomain($model) : null;
    }

    public function save(Credential $credential): Credential
    {
        $model = $credential->id === null
            ? new CredentialModel
            : CredentialModel::query()->findOrFail($credential->id);

        $this->mapper->fillModel($credential, $model)->save();

        return $this->mapper->toDomain(
            $model->refresh()->load([
                'account' => fn ($query) => $query->withTrashed(),
                'secret',
            ]),
        );
    }

    public function labelExists(int $serviceAccountId, string $label, ?int $ignoreId = null): bool
    {
        return CredentialModel::query()
            ->where('service_account_id', $serviceAccountId)
            ->where('label', $label)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    public function secretPayload(int $credentialId): ?array
    {
        $secret = CredentialSecretModel::query()
            ->where('credential_reference_id', $credentialId)
            ->first();

        if ($secret === null) {
            return null;
        }

        return [
            'ciphertext' => $secret->ciphertext,
            'key_id' => $secret->key_id,
            'version' => $secret->version,
        ];
    }

    public function storeSecret(
        int $credentialId,
        string $ciphertext,
        string $keyId,
        int $actorId,
    ): int {
        $secret = CredentialSecretModel::query()
            ->where('credential_reference_id', $credentialId)
            ->first();

        if ($secret === null) {
            $secret = new CredentialSecretModel;
            $secret->credential_reference_id = $credentialId;
            $secret->version = 1;
        } else {
            $secret->version++;
        }

        $secret->ciphertext = $ciphertext;
        $secret->key_id = $keyId;
        $secret->rotated_by = $actorId;
        $secret->save();

        CredentialModel::query()
            ->whereKey($credentialId)
            ->update([
                'last_rotated_at' => now(),
                'updated_at' => now(),
            ]);

        return $secret->version;
    }
}
