<?php

use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Shared\Domain\Exceptions\DomainException;

test('a credential is created active without a secret', function () {
    $credential = Credential::create(
        companyId: 1,
        serviceAccountId: 10,
        accountUsername: 'juan.perez',
        accountInstance: 'cloud.example.test',
        label: '  Contraseña principal  ',
        kind: CredentialKind::Password,
    );

    expect($credential->label)->toBe('Contraseña principal')
        ->and($credential->status)->toBe(CredentialStatus::Active)
        ->and($credential->hasSecret)->toBeFalse()
        ->and($credential->lastRotatedAt)->toBeNull();
});

test('a credential requires company account and label', function () {
    expect(fn () => Credential::create(
        companyId: 0,
        serviceAccountId: 10,
        accountUsername: 'juan',
        accountInstance: null,
        label: 'Principal',
        kind: CredentialKind::Password,
    ))->toThrow(DomainException::class);

    expect(fn () => Credential::create(
        companyId: 1,
        serviceAccountId: 0,
        accountUsername: 'juan',
        accountInstance: null,
        label: 'Principal',
        kind: CredentialKind::Password,
    ))->toThrow(DomainException::class);

    expect(fn () => Credential::create(
        companyId: 1,
        serviceAccountId: 10,
        accountUsername: 'juan',
        accountInstance: null,
        label: '   ',
        kind: CredentialKind::Password,
    ))->toThrow(DomainException::class);
});

test('credential status transitions are explicit and revocation is terminal', function () {
    $credential = Credential::create(
        companyId: 1,
        serviceAccountId: 10,
        accountUsername: 'juan',
        accountInstance: null,
        label: 'Principal',
        kind: CredentialKind::Password,
    );

    expect(CredentialStatus::Active->canTransitionTo(CredentialStatus::Revoked))->toBeTrue()
        ->and(CredentialStatus::Active->canTransitionTo(CredentialStatus::Active))->toBeTrue()
        ->and(CredentialStatus::Revoked->canTransitionTo(CredentialStatus::Active))->toBeFalse();

    $credential->changeStatus(CredentialStatus::Revoked);

    expect($credential->status)->toBe(CredentialStatus::Revoked);

    expect(fn () => $credential->changeStatus(CredentialStatus::Active))
        ->toThrow(DomainException::class);
});

test('only credentials with an active secret can be revealed', function () {
    $credential = Credential::create(
        companyId: 1,
        serviceAccountId: 10,
        accountUsername: 'juan',
        accountInstance: null,
        label: 'Principal',
        kind: CredentialKind::Password,
    );

    expect(fn () => $credential->assertCanReveal())->toThrow(DomainException::class);

    $credential->hasSecret = true;

    expect(fn () => $credential->assertCanReveal())->not->toThrow(DomainException::class);

    $credential->changeStatus(CredentialStatus::Revoked);

    expect(fn () => $credential->assertCanReveal())->toThrow(DomainException::class);
});
