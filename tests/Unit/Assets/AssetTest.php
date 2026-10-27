<?php

use App\Assets\Domain\Entities\Asset;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Shared\Domain\Exceptions\DomainException;

test('an asset is created active with normalized values', function () {
    $asset = Asset::create(
        companyId: 1,
        internalCode: '  ACT-001  ',
        category: AssetCategory::Desktop,
        locationId: 2,
        hostname: '  PC-RECEPCION-01  ',
        brand: '  Dell  ',
    );

    expect($asset->internalCode)->toBe('ACT-001')
        ->and($asset->hostname)->toBe('pc-recepcion-01')
        ->and($asset->brand)->toBe('Dell')
        ->and($asset->status)->toBe(AssetStatus::Active);
});

test('an asset requires an internal code and a location', function () {
    expect(fn () => Asset::create(
        companyId: 1,
        internalCode: '   ',
        category: AssetCategory::Desktop,
        locationId: 2,
    ))->toThrow(DomainException::class);

    expect(fn () => Asset::create(
        companyId: 1,
        internalCode: 'ACT-001',
        category: AssetCategory::Desktop,
        locationId: 0,
    ))->toThrow(DomainException::class);
});

test('warranty can not expire before purchase date', function () {
    expect(fn () => Asset::create(
        companyId: 1,
        internalCode: 'ACT-001',
        category: AssetCategory::Desktop,
        locationId: 2,
        purchaseDate: new DateTimeImmutable('2026-01-10'),
        warrantyUntil: new DateTimeImmutable('2026-01-09'),
    ))->toThrow(DomainException::class);
});

test('asset status transitions are explicit', function () {
    expect(AssetStatus::Active->canTransitionTo(AssetStatus::InMaintenance))->toBeTrue()
        ->and(AssetStatus::Active->canTransitionTo(AssetStatus::InStorage))->toBeTrue()
        ->and(AssetStatus::Active->canTransitionTo(AssetStatus::Retired))->toBeTrue()
        ->and(AssetStatus::Active->canTransitionTo(AssetStatus::Lost))->toBeTrue()
        ->and(AssetStatus::InMaintenance->canTransitionTo(AssetStatus::Active))->toBeTrue()
        ->and(AssetStatus::InStorage->canTransitionTo(AssetStatus::Active))->toBeTrue()
        ->and(AssetStatus::Lost->canTransitionTo(AssetStatus::Retired))->toBeTrue()
        ->and(AssetStatus::Retired->canTransitionTo(AssetStatus::Active))->toBeFalse()
        ->and(AssetStatus::Active->canTransitionTo(AssetStatus::Active))->toBeTrue();
});

test('an invalid asset status transition is rejected', function () {
    $asset = Asset::create(
        companyId: 1,
        internalCode: 'ACT-001',
        category: AssetCategory::Desktop,
        locationId: 2,
    );

    $asset->changeStatus(AssetStatus::Retired);

    expect(fn () => $asset->changeStatus(AssetStatus::Active))
        ->toThrow(DomainException::class);
});
