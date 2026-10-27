<?php

namespace App\Credentials\Application\UseCases;

use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Shared\Domain\Pagination\Page;

final readonly class SearchCredentials
{
    public function __construct(private CredentialRepository $credentials) {}

    /**
     * @return Page<Credential>
     */
    public function execute(
        int $companyId,
        ?string $term = null,
        int $page = 1,
        int $perPage = 15,
        ?int $serviceAccountId = null,
        ?CredentialStatus $status = null,
        ?CredentialKind $kind = null,
    ): Page {
        $term = $term !== null ? trim($term) : null;

        return $this->credentials->search(
            companyId: $companyId,
            term: $term === '' ? null : $term,
            page: max(1, $page),
            perPage: min(100, max(1, $perPage)),
            serviceAccountId: $serviceAccountId,
            status: $status,
            kind: $kind,
        );
    }
}
