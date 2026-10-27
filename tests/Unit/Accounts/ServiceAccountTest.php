<?php

use App\Accounts\Domain\Entities\AccountAssetLink;
use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Shared\Domain\Exceptions\DomainException;

test('a service account is created active with normalized values', function () {
    $account = ServiceAccount::create(
        companyId: 1,
        service: ServiceType::Nextcloud,
        username: '  juan.perez  ',
        instance: '  CLOUD.EMPRESA.COM  ',
        ownerName: '  Juan Pérez  ',
    );

    expect($account->username)->toBe('juan.perez')
        ->and($account->instance)->toBe('cloud.empresa.com')
        ->and($account->ownerName)->toBe('Juan Pérez')
        ->and($account->status)->toBe(ServiceAccountStatus::Active);
});

test('a service account requires company username and instance', function () {
    expect(fn () => ServiceAccount::create(
        companyId: 0,
        service: ServiceType::Nextcloud,
        username: 'juan',
        instance: 'cloud.example.test',
    ))->toThrow(DomainException::class);

    expect(fn () => ServiceAccount::create(
        companyId: 1,
        service: ServiceType::Nextcloud,
        username: '   ',
        instance: 'cloud.example.test',
    ))->toThrow(DomainException::class);

    expect(fn () => ServiceAccount::create(
        companyId: 1,
        service: ServiceType::Nextcloud,
        username: 'juan',
        instance: '   ',
    ))->toThrow(DomainException::class);
});

test('service account status transitions are explicit', function () {
    expect(ServiceAccountStatus::Active->canTransitionTo(ServiceAccountStatus::Suspended))->toBeTrue()
        ->and(ServiceAccountStatus::Active->canTransitionTo(ServiceAccountStatus::Revoked))->toBeTrue()
        ->and(ServiceAccountStatus::Suspended->canTransitionTo(ServiceAccountStatus::Active))->toBeTrue()
        ->and(ServiceAccountStatus::Suspended->canTransitionTo(ServiceAccountStatus::Revoked))->toBeTrue()
        ->and(ServiceAccountStatus::Revoked->canTransitionTo(ServiceAccountStatus::Active))->toBeFalse()
        ->and(ServiceAccountStatus::Active->canTransitionTo(ServiceAccountStatus::Active))->toBeTrue();
});

test('only active service accounts can link assets', function () {
    $account = ServiceAccount::create(
        companyId: 1,
        service: ServiceType::Nextcloud,
        username: 'juan',
        instance: 'cloud.example.test',
    );

    $account->changeStatus(ServiceAccountStatus::Suspended);

    expect(fn () => $account->assertCanLinkAssets())->toThrow(DomainException::class);

    $account->changeStatus(ServiceAccountStatus::Active);

    expect(fn () => $account->assertCanLinkAssets())->not->toThrow(DomainException::class);
});

test('active account links exclude closed history', function () {
    $account = ServiceAccount::create(
        companyId: 1,
        service: ServiceType::Nextcloud,
        username: 'juan',
        instance: 'cloud.example.test',
    );

    $account->links = [
        new AccountAssetLink(1, 1, 10, 'ACT-001', 'pc-001', new DateTimeImmutable('2026-01-01'), null, 'Inicial', null),
        new AccountAssetLink(2, 1, 11, 'ACT-002', 'pc-002', new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-02-01'), 'Inicial', 'Sustitución'),
    ];

    expect($account->activeLinks())->toHaveCount(1)
        ->and($account->activeLinks()[0]->assetInternalCode)->toBe('ACT-001');
});
