<?php

namespace App\Companies\Infrastructure\Persistence\Eloquent\Mappers;

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;

final class CompanyMapper
{
    public function toDomain(CompanyModel $model): Company
    {
        return new Company(
            id: $model->id,
            publicId: $model->public_id,
            name: $model->name,
            legalName: $model->legal_name,
            taxId: $model->tax_id,
            email: $model->email,
            phone: $model->phone,
            website: $model->website,
            industry: $model->industry,
            status: CompanyStatus::from($model->status),
            timezone: $model->timezone,
            locale: $model->locale,
            logoPath: $model->logo_path,
            settings: $model->settings,
            createdBy: $model->created_by,
            usersCount: (int) ($model->users_count ?? 0),
            departmentsCount: (int) ($model->departments_count ?? 0),
        );
    }

    public function fillModel(Company $company, CompanyModel $model): CompanyModel
    {
        $model->public_id = $company->publicId;
        $model->name = $company->name;
        $model->legal_name = $company->legalName;
        $model->tax_id = $company->taxId;
        $model->email = $company->email;
        $model->phone = $company->phone;
        $model->website = $company->website;
        $model->industry = $company->industry;
        $model->status = $company->status->value;
        $model->timezone = $company->timezone;
        $model->locale = $company->locale;
        $model->logo_path = $company->logoPath;
        $model->settings = $company->settings;
        $model->created_by = $company->createdBy;

        return $model;
    }
}
