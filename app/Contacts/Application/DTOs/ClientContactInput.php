<?php

namespace App\Contacts\Application\DTOs;

final readonly class ClientContactInput
{
    public function __construct(
        public string $name,
        public ?int $locationId = null,
        public ?string $position = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $whatsapp = null,
        public bool $isPrimary = false,
        public ?string $notes = null,
        public bool $isActive = true,
    ) {}
}
