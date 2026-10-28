<?php

namespace App\Companies\Application\UseCases;

use App\Companies\Application\DTOs\CompanyInput;
use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\AuditLogger;
use Illuminate\Support\Str;

final readonly class CreateCompany
{
    public function __construct(
        private CompanyRepository $companies,
        private AuditLogger $audit,
    ) {}

    public function execute(CompanyInput $input, int $actorId): Company
    {
        $company = Company::create(
            publicId: (string) Str::ulid(),
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
            createdBy: $actorId,
        );

        $saved = $this->companies->save($company);

        $this->audit->log(
            event: 'company.created',
            description: "Se creó la empresa {$saved->name}.",
            subjectType: CompanyModel::class,
            subjectId: $saved->id,
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
            'industry' => $company->industry,
            'status' => $company->status->value,
            'features' => $this->features($company),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function features(Company $company): array
    {
        $features = $company->settings['features'] ?? [];

        return is_array($features) ? $features : [];
    }
}
