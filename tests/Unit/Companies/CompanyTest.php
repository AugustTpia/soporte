<?php

use App\Companies\Domain\Entities\Company;
use App\Companies\Domain\Enums\CompanyStatus;
use App\Shared\Domain\Exceptions\DomainException;

test('a company can be created with defaults', function () {
    $company = Company::create(
        publicId: '01JABCXYZ',
        name: '  Empresa Demo  ',
    );

    expect($company->name)->toBe('Empresa Demo');
    expect($company->timezone)->toBe('America/Mexico_City');
    expect($company->locale)->toBe('es_MX');
    expect($company->status)->toBe(CompanyStatus::Active);
    expect($company->isActive())->toBeTrue();
});

test('a company requires a name', function () {
    expect(fn () => Company::create(publicId: '01JABCXYZ', name: '   '))
        ->toThrow(DomainException::class, 'El nombre de la empresa es obligatorio.');
});

test('a company rejects invalid timezones', function () {
    expect(fn () => Company::create(
        publicId: '01JABCXYZ',
        name: 'Empresa Demo',
        timezone: 'Mars/Olympus',
    ))->toThrow(DomainException::class);
});

test('a company can change status', function () {
    $company = Company::create(publicId: '01JABCXYZ', name: 'Empresa Demo');

    $company->changeStatus(CompanyStatus::Inactive);

    expect($company->isActive())->toBeFalse();
});
