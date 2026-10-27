<?php

namespace App\Companies\Application\DTOs;

final readonly class CompanyInput
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public string $name,
        public ?string $legalName = null,
        public ?string $taxId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $website = null,
        public ?string $industry = null,
        public string $timezone = 'America/Mexico_City',
        public string $locale = 'es_MX',
        public ?array $settings = null,
    ) {}
}
