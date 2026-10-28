<?php

namespace App\Contacts\Domain\Repositories;

use App\Contacts\Domain\Entities\ClientContact;

interface ClientContactRepository
{
    /**
     * @return array<int, ClientContact>
     */
    public function forCompany(int $companyId, bool $activeOnly = false, ?int $includeId = null): array;

    public function find(int $id, int $companyId): ?ClientContact;

    public function save(ClientContact $contact): ClientContact;

    public function clearPrimary(int $companyId, ?int $ignoreId = null): void;

    public function countActive(int $companyId): int;
}
