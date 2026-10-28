<?php

namespace App\Contacts\Infrastructure\Persistence\Eloquent\Mappers;

use App\Contacts\Domain\Entities\ClientContact;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;

final class ClientContactMapper
{
    public function toDomain(ClientContactModel $model): ClientContact
    {
        return new ClientContact(
            id: $model->id,
            companyId: $model->company_id,
            locationId: $model->location_id,
            name: $model->name,
            position: $model->position,
            email: $model->email,
            phone: $model->phone,
            whatsapp: $model->whatsapp,
            isPrimary: $model->is_primary,
            isActive: $model->is_active,
            notes: $model->notes,
            locationName: $model->relationLoaded('location') ? $model->location?->name : null,
        );
    }

    public function fillModel(ClientContact $contact, ClientContactModel $model): ClientContactModel
    {
        $model->company_id = $contact->companyId;
        $model->location_id = $contact->locationId;
        $model->name = $contact->name;
        $model->position = $contact->position;
        $model->email = $contact->email;
        $model->phone = $contact->phone;
        $model->whatsapp = $contact->whatsapp;
        $model->is_primary = $contact->isPrimary;
        $model->is_active = $contact->isActive;
        $model->notes = $contact->notes;

        return $model;
    }
}
