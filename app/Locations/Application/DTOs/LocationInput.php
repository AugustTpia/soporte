<?php

namespace App\Locations\Application\DTOs;

final readonly class LocationInput
{
    public function __construct(
        public string $name,
        public string $street,
        public string $exteriorNumber,
        public string $city,
        public string $country = 'México',
        public ?string $interiorNumber = null,
        public ?string $neighborhood = null,
        public ?string $state = null,
        public ?string $postalCode = null,
        public ?string $phone = null,
        public ?string $notes = null,
        public bool $isActive = true,
    ) {}
}
