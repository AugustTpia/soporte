<?php

namespace App\Credentials\Application\DTOs;

use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;

final readonly class CredentialInput
{
    public function __construct(
        public string $label,
        public CredentialKind $kind,
        public CredentialStatus $status,
    ) {}
}
