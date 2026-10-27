<?php

use App\Identity\Domain\Enums\RoleName;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Locations\Presentation\Livewire\LocationForm;
use App\Locations\Presentation\Livewire\LocationIndex;
use Livewire\Livewire;

test('company administrators can create locations for their company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(LocationForm::class)
        ->set('name', 'Matriz')
        ->set('street', 'Av. Reforma')
        ->set('exteriorNumber', '123')
        ->set('interiorNumber', '4B')
        ->set('neighborhood', 'Centro')
        ->set('city', 'Ciudad de México')
        ->set('state', 'CDMX')
        ->set('postalCode', '06000')
        ->set('country', 'México')
        ->set('phone', '5512345678')
        ->set('notes', 'Acceso por recepción')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('locations.index'));

    $this->assertDatabaseHas('locations', [
        'company_id' => $company->id,
        'name' => 'Matriz',
        'slug' => 'matriz',
        'street' => 'Av. Reforma',
        'exterior_number' => '123',
        'interior_number' => '4B',
        'city' => 'Ciudad de México',
        'country' => 'México',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'location.created',
        'company_id' => $company->id,
    ]);
});

test('a location requires a city', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(LocationForm::class)
        ->set('name', 'Matriz')
        ->set('street', 'Av. Reforma')
        ->set('exteriorNumber', '123')
        ->set('city', '')
        ->call('save')
        ->assertHasErrors(['city' => 'required']);

    $this->assertDatabaseCount('locations', 0);
});

test('company administrators can update locations and their city', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create([
        'company_id' => $company->id,
        'city' => 'Ciudad de México',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(LocationForm::class, ['location' => $location])
        ->set('city', 'Guadalajara')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('locations.index'));

    expect($location->refresh()->city)->toBe('Guadalajara');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'location.updated',
        'company_id' => $company->id,
    ]);
});

test('duplicate location names are rejected per company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    LocationModel::factory()->create([
        'company_id' => $company->id,
        'name' => 'Matriz',
        'slug' => 'matriz',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(LocationForm::class)
        ->set('name', 'Matriz')
        ->set('street', 'Av. Reforma')
        ->set('exteriorNumber', '123')
        ->set('city', 'Ciudad de México')
        ->call('save')
        ->assertHasErrors('form');
});

test('company administrators can not edit locations from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $location = LocationModel::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this->actingAs($companyAdmin)
        ->get(route('locations.edit', $location))
        ->assertForbidden();
});

test('company administrators can toggle location status', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $location = LocationModel::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(LocationIndex::class)
        ->call('toggleStatus', $location->id)
        ->assertHasNoErrors();

    expect($location->refresh()->is_active)->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'location.status_changed',
        'company_id' => $company->id,
    ]);
});

test('service managers can view locations but not manage them', function () {
    $company = createTestCompany();
    $serviceManager = createTestUser($company, [], RoleName::ServiceManager);

    $this->actingAs($serviceManager)
        ->get(route('locations.index'))
        ->assertOk();

    $this->actingAs($serviceManager)
        ->get(route('locations.create'))
        ->assertForbidden();
});

test('locations require an active tenant', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin)
        ->get(route('locations.index'))
        ->assertRedirect(route('companies.index'));
});

test('super admins only manage locations from the active company', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();
    $otherCompany = createTestCompany();

    $location = LocationModel::factory()->create([
        'company_id' => $company->id,
        'name' => 'Matriz Empresa A',
        'slug' => 'matriz-empresa-a',
    ]);
    $otherLocation = LocationModel::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Matriz Empresa B',
        'slug' => 'matriz-empresa-b',
    ]);

    session(['active_company_id' => $company->id]);

    $this->actingAs($superAdmin)
        ->get(route('locations.index'))
        ->assertOk()
        ->assertSee('Matriz Empresa A')
        ->assertDontSee('Matriz Empresa B');

    $this->actingAs($superAdmin)
        ->get(route('locations.edit', $otherLocation))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('locations.edit', $location))
        ->assertOk();
});
