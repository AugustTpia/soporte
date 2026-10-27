<?php

namespace App\Accounts\Application\DTOs;

use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;

final readonly class ServiceAccountInput
{
    public function __construct(
        public ServiceType $service,
        public string $username,
        public string $instance,
        public ?string $ownerName,
        public ServiceAccountStatus $status,
        public ?int $responsibleUserId,
        public ?string $notes,
    ) {}
}
