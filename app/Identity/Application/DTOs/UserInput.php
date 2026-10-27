<?php

namespace App\Identity\Application\DTOs;

final readonly class UserInput
{
    /**
     * @param  array<int, string>  $roles
     * @param  array<int, int>  $departmentIds
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?int $companyId,
        public ?string $jobTitle = null,
        public ?string $phone = null,
        public ?string $password = null,
        public array $roles = [],
        public array $departmentIds = [],
    ) {}
}
