<?php

namespace App\Companies\Application\UseCases;

use App\Companies\Application\DTOs\CompanyInput;
use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateCompany
{
    public function __construct(
        private CompanyRepository $companies,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, CompanyInput $input): Company
    {
        $company = $this->companies->find($companyId)
            ?? throw DomainException::because('La empresa no existe.');

        $before = $this->safeValues($company);

        $company->updateDetails(
            name: $input->name,
            legalName: $input->legalName,
            taxId: $input->taxId,
            email: $input->email,
            phone: $input->phone,
            website: $input->website,
            industry: $input->industry,
            timezone: $input->timezone,
            locale: $input->locale,
            settings: $input->settings,
        );

        $saved = $this->companies->save($company);

        $this->audit->log(
            event: 'company.updated',
            description: "Se actualizó la empresa {$saved->name}.",
            subjectType: CompanyModel::class,
            subjectId: $saved->id,
            oldValues: $before,
            newValues: $this->safeValues($saved),
            companyId: $saved->id,
        );

        return $saved;
    }

    /**
     * @return array<string, mixed>
     */
    private function safeValues(Company $company): array
    {
        return [
            'name' => $company->name,
            'legal_name' => $company->legalName,
            'tax_id' => $company->taxId,
            'email' => $company->email,
            'phone' => $company->phone,
            'website' => $company->website,
            'industry' => $company->industry,
            'timezone' => $company->timezone,
            'locale' => $company->locale,
            'status' => $company->status->value,
        ];
    }
}
