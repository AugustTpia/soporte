<?php

use App\Departments\Domain\Entities\Department;
use App\Shared\Domain\Exceptions\DomainException;

test('a department is created active', function () {
    $department = Department::create(
        companyId: 1,
        name: '  Soporte  ',
        slug: 'soporte',
    );

    expect($department->name)->toBe('Soporte');
    expect($department->isActive)->toBeTrue();
});

test('a department can be deactivated and activated', function () {
    $department = Department::create(companyId: 1, name: 'Soporte', slug: 'soporte');

    $department->deactivate();
    expect($department->isActive)->toBeFalse();

    $department->activate();
    expect($department->isActive)->toBeTrue();
});

test('a department requires a name and slug', function () {
    expect(fn () => Department::create(companyId: 1, name: '', slug: 'soporte'))
        ->toThrow(DomainException::class);

    expect(fn () => Department::create(companyId: 1, name: 'Soporte', slug: '   '))
        ->toThrow(DomainException::class);
});

test('a department name can not exceed 120 characters', function () {
    expect(fn () => Department::create(
        companyId: 1,
        name: str_repeat('a', 121),
        slug: 'soporte',
    ))->toThrow(DomainException::class);
});
