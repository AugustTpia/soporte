<?php

use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

test('company users can open the profile of their own company', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    ClientContactModel::factory()->forCompany($company)->create(['name' => 'Contacto de ficha']);

    $this->actingAs($admin)
        ->get(route('companies.show', $company->public_id))
        ->assertOk()
        ->assertSee($company->name)
        ->assertSee('Contacto de ficha')
        ->assertSee('Contactos activos');
});

test('company users cannot open another company profile', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin)
        ->get(route('companies.show', $otherCompany->public_id))
        ->assertNotFound();
});

test('super administrators can open any company profile and activate its tenant', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin)
        ->get(route('companies.show', $company->public_id))
        ->assertOk()
        ->assertSee($company->name);

    expect(session('active_company_id'))->toBe($company->id);
});

test('the profile hides modules without permission', function () {
    $company = createTestCompany();
    seedRoles();
    $user = User::factory()->forCompany($company)->create();
    $user->givePermissionTo('companies.view');

    SupportTicketModel::factory()->forCompany($company)->create();

    $this->actingAs($user)
        ->get(route('companies.show', $company->public_id))
        ->assertOk()
        ->assertDontSee('Tickets abiertos')
        ->assertDontSee('Sedes activas');
});

test('the cancellation card is hidden when the feature is disabled', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($admin)
        ->get(route('companies.show', $company->public_id))
        ->assertOk()
        ->assertDontSee('Cancelaciones 30 días');
});
