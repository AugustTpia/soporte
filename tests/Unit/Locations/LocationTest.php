<?php

use App\Locations\Domain\Entities\Location;
use App\Shared\Domain\Exceptions\DomainException;

test('a location is created active with normalized values', function () {
    $location = Location::create(
        companyId: 1,
        name: '  Matriz  ',
        slug: 'matriz',
        street: '  Av. Reforma  ',
        exteriorNumber: ' 123 ',
        interiorNumber: '   ',
        city: '  Ciudad de México  ',
        country: '  México  ',
    );

    expect($location->name)->toBe('Matriz');
    expect($location->street)->toBe('Av. Reforma');
    expect($location->exteriorNumber)->toBe('123');
    expect($location->interiorNumber)->toBeNull();
    expect($location->city)->toBe('Ciudad de México');
    expect($location->country)->toBe('México');
    expect($location->isActive)->toBeTrue();
});

test('a location can be deactivated and activated', function () {
    $location = Location::create(
        companyId: 1,
        name: 'Matriz',
        slug: 'matriz',
        street: 'Av. Reforma',
        exteriorNumber: '123',
        city: 'Ciudad de México',
    );

    $location->deactivate();
    expect($location->isActive)->toBeFalse();

    $location->activate();
    expect($location->isActive)->toBeTrue();
});

test('a location requires street exterior number city and country', function () {
    $attributes = [
        'companyId' => 1,
        'name' => 'Matriz',
        'slug' => 'matriz',
        'street' => 'Av. Reforma',
        'exteriorNumber' => '123',
        'city' => 'Ciudad de México',
        'country' => 'México',
    ];

    foreach (['street', 'exteriorNumber', 'city', 'country'] as $field) {
        $invalid = [...$attributes, $field => '   '];

        expect(fn () => Location::create(...$invalid))
            ->toThrow(DomainException::class);
    }
});

test('a location city can not exceed 120 characters', function () {
    expect(fn () => Location::create(
        companyId: 1,
        name: 'Matriz',
        slug: 'matriz',
        street: 'Av. Reforma',
        exteriorNumber: '123',
        city: str_repeat('a', 121),
    ))->toThrow(DomainException::class);
});
