<?php

use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Contacts\Presentation\Livewire\CompanyContacts;
use App\Identity\Domain\Enums\RoleName;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Livewire\Livewire;

test('company administrators can create contacts from the client profile', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($admin);

    Livewire::test(CompanyContacts::class, ['companyId' => $company->id])
        ->call('openCreate')
        ->set('name', 'Laura Méndez')
        ->set('position', 'Coordinadora de sistemas')
        ->set('email', 'laura@example.test')
        ->set('phone', '5512345678')
        ->set('whatsapp', '5512345678')
        ->set('locationId', (string) $location->id)
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('client_contacts', [
        'company_id' => $company->id,
        'location_id' => $location->id,
        'name' => 'Laura Méndez',
        'email' => 'laura@example.test',
        'is_primary' => true,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'client_contact.created',
        'company_id' => $company->id,
    ]);
});

test('only one primary contact remains active per company', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $first = ClientContactModel::factory()->forCompany($company)->create([
        'name' => 'Primero',
        'is_primary' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CompanyContacts::class, ['companyId' => $company->id])
        ->call('openCreate')
        ->set('name', 'Segundo')
        ->set('isPrimary', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($first->refresh()->is_primary)->toBeFalse();

    expect(ClientContactModel::query()
        ->where('company_id', $company->id)
        ->where('is_primary', true)
        ->count())->toBe(1);
});

test('contacts reject locations from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $foreignLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($admin);

    Livewire::test(CompanyContacts::class, ['companyId' => $company->id])
        ->call('openCreate')
        ->set('name', 'Contacto inválido')
        ->set('locationId', (string) $foreignLocation->id)
        ->call('save')
        ->assertHasErrors(['locationId']);

    $this->assertDatabaseCount('client_contacts', 0);
});

test('contacts can be deactivated without losing their history', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $contact = ClientContactModel::factory()->forCompany($company)->create([
        'is_primary' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CompanyContacts::class, ['companyId' => $company->id])
        ->call('toggleStatus', $contact->id)
        ->assertHasNoErrors();

    expect($contact->refresh())
        ->is_active->toBeFalse()
        ->is_primary->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'client_contact.deactivated',
        'company_id' => $company->id,
    ]);
});

test('contacts cannot be managed from another tenant', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin);

    Livewire::test(CompanyContacts::class, ['companyId' => $otherCompany->id])
        ->assertNotFound();
});

test('read only users can view contacts but cannot manage them', function () {
    $company = createTestCompany();
    $readOnly = createTestUser($company, [], RoleName::ReadOnly);
    ClientContactModel::factory()->forCompany($company)->create(['name' => 'Contacto visible']);

    $this->actingAs($readOnly);

    Livewire::test(CompanyContacts::class, ['companyId' => $company->id])
        ->assertSee('Contacto visible')
        ->assertDontSee('Nuevo contacto')
        ->call('openCreate')
        ->assertForbidden();
});
