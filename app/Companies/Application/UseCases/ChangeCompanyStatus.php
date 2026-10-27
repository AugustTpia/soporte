<?php

namespace App\Companies\Application\UseCases;

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ChangeCompanyStatus
{
    public function __construct(
        private CompanyRepository $companies,
        private AuditLogger $audit,
    ) {}

    public function execute(int $companyId, CompanyStatus $status): Company
    {
        $company = $this->companies->find($companyId)
            ?? throw DomainException::because('La empresa no existe.');

        $previous = $company->status;
        $company->changeStatus($status);
        $saved = $this->companies->save($company);

        $this->audit->log(
            event: 'company.status_changed',
            description: "Se cambió el estado de la empresa {$saved->name} a {$status->label()}.",
            subjectType: CompanyModel::class,
            subjectId: $saved->id,
            oldValues: ['status' => $previous->value],
            newValues: ['status' => $saved->status->value],
            companyId: $saved->id,
        );

        return $saved;
    }
}
