<?php

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\RoleName;
use App\Shared\Domain\Exceptions\DomainException;

test('a user normalizes name and email', function () {
    $user = User::create(
        companyId: 1,
        name: '  Ana Pérez  ',
        email: '  ANA@EXAMPLE.COM ',
    );

    expect($user->name)->toBe('Ana Pérez');
    expect($user->email)->toBe('ana@example.com');
    expect($user->status->value)->toBe('active');
});

test('a user requires a valid email and name', function () {
    expect(fn () => User::create(companyId: 1, name: '', email: 'ana@example.com'))
        ->toThrow(DomainException::class);

    expect(fn () => User::create(companyId: 1, name: 'Ana', email: 'not-an-email'))
        ->toThrow(DomainException::class);
});

test('a user can not have duplicate roles', function () {
    expect(fn () => User::create(
        companyId: 1,
        name: 'Ana',
        email: 'ana@example.com',
        roles: [RoleName::Technician->value, RoleName::Technician->value],
    ))->toThrow(DomainException::class);
});

test('a user can be deactivated and activated', function () {
    $user = User::create(companyId: 1, name: 'Ana', email: 'ana@example.com');

    $user->deactivate();
    expect($user->isActive())->toBeFalse();

    $user->activate();
    expect($user->isActive())->toBeTrue();
});

test('a user exposes initials and primary role', function () {
    $user = User::create(
        companyId: 1,
        name: 'Ana María Pérez',
        email: 'ana@example.com',
        roles: [RoleName::Technician->value],
    );

    expect($user->initials())->toBe('AM');
    expect($user->primaryRole())->toBe(RoleName::Technician);
});
