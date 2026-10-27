<?php

namespace App\Shared\Application\Contracts;

interface AuditLogger
{
    /**
     * Record a security or business event without storing secrets.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $event,
        string $description,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?int $companyId = null,
    ): void;
}
