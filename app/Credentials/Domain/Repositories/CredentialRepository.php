<?php

namespace App\Credentials\Domain\Repositories;

use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Shared\Domain\Pagination\Page;

interface CredentialRepository
{
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
    ): Page;

    public function find(int $id, int $companyId): ?Credential;

    public function save(Credential $credential): Credential;

    public function labelExists(int $serviceAccountId, string $label, ?int $ignoreId = null): bool;

    /**
     * @return array{ciphertext: string, key_id: string, version: int}|null
     */
    public function secretPayload(int $credentialId): ?array;

    public function storeSecret(
        int $credentialId,
        string $ciphertext,
        string $keyId,
        int $actorId,
    ): int;
}
