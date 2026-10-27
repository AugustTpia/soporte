<?php

namespace App\Locations\Infrastructure\Persistence\Eloquent\Mappers;

use App\Locations\Domain\Entities\Location;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;

final class LocationMapper
{
    public function toDomain(LocationModel $model): Location
    {
        return new Location(
            id: $model->id,
            companyId: $model->company_id,
            name: $model->name,
            slug: $model->slug,
            street: $model->street,
            exteriorNumber: $model->exterior_number,
            interiorNumber: $model->interior_number,
            neighborhood: $model->neighborhood,
            city: $model->city,
            state: $model->state,
            postalCode: $model->postal_code,
            country: $model->country,
            phone: $model->phone,
            notes: $model->notes,
            isActive: (bool) $model->is_active,
        );
    }

    public function fillModel(Location $location, LocationModel $model): LocationModel
    {
        $model->company_id = $location->companyId;
        $model->name = $location->name;
        $model->slug = $location->slug;
        $model->street = $location->street;
        $model->exterior_number = $location->exteriorNumber;
        $model->interior_number = $location->interiorNumber;
        $model->neighborhood = $location->neighborhood;
        $model->city = $location->city;
        $model->state = $location->state;
        $model->postal_code = $location->postalCode;
        $model->country = $location->country;
        $model->phone = $location->phone;
        $model->notes = $location->notes;
        $model->is_active = $location->isActive;

        return $model;
    }
}
