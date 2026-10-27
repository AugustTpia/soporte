<?php

namespace App\Companies\Domain\Entities;

use App\Companies\Domain\Enums\CompanyStatus;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeZone;
use Exception;

final class Company
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public ?int $id,
        public string $publicId,
        public string $name,
        public ?string $legalName,
        public ?string $taxId,
        public ?string $email,
        public ?string $phone,
        public ?string $website,
        public ?string $industry,
        public CompanyStatus $status,
        public string $timezone,
        public string $locale,
        public ?string $logoPath = null,
        public ?array $settings = null,
        public ?int $createdBy = null,
        public int $usersCount = 0,
        public int $departmentsCount = 0,
    ) {
        $this->guard();
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public static function create(
        string $publicId,
        string $name,
        ?string $legalName = null,
        ?string $taxId = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $website = null,
        ?string $industry = null,
        string $timezone = 'America/Mexico_City',
        string $locale = 'es_MX',
        ?array $settings = null,
        ?int $createdBy = null,
    ): self {
        return new self(
            id: null,
            publicId: $publicId,
            name: $name,
            legalName: $legalName,
            taxId: $taxId,
            email: $email,
            phone: $phone,
            website: $website,
            industry: $industry,
            status: CompanyStatus::Active,
            timezone: $timezone,
            locale: $locale,
            settings: $settings,
            createdBy: $createdBy,
        );
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function updateDetails(
        string $name,
        ?string $legalName,
        ?string $taxId,
        ?string $email,
        ?string $phone,
        ?string $website,
        ?string $industry,
        string $timezone,
        string $locale,
        ?array $settings = null,
    ): void {
        $this->name = $name;
        $this->legalName = $legalName;
        $this->taxId = $taxId;
        $this->email = $email;
        $this->phone = $phone;
        $this->website = $website;
        $this->industry = $industry;
        $this->timezone = $timezone;
        $this->locale = $locale;
        $this->settings = $settings;

        $this->guard();
    }

    public function changeStatus(CompanyStatus $status): void
    {
        $this->status = $status;
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }

    private function guard(): void
    {
        $this->name = trim($this->name);

        if ($this->name === '') {
            throw DomainException::because('El nombre de la empresa es obligatorio.');
        }

        if (mb_strlen($this->name) > 160) {
            throw DomainException::because('El nombre de la empresa no puede superar 160 caracteres.');
        }

        try {
            new DateTimeZone($this->timezone);
        } catch (Exception) {
            throw DomainException::because('La zona horaria de la empresa no es válida.');
        }
    }
}
