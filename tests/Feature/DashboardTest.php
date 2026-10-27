<?php

use App\Companies\Domain\Enums\CompanyStatus;
use App\Identity\Domain\Enums\RoleName;
use App\Shared\Application\Contracts\AuditLogger;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with an active company can visit the dashboard', function () {
    $user = createTestUser(createTestCompany());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('super admins can visit the global dashboard without an active company', function () {
    $user = createTestSuperAdmin();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Todas las empresas')
        ->assertSee('Selecciona una empresa para administrar su equipo')
        ->assertDontSee('href="'.route('users.index').'"', false)
        ->assertDontSee('href="'.route('departments.index').'"', false)
        ->assertDontSee('href="'.route('locations.index').'"', false)
        ->assertDontSee('href="'.route('assets.index').'"', false);
});

test('super admins with an active company see administrative modules', function () {
    $user = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('users.index').'"', false)
        ->assertSee('href="'.route('departments.index').'"', false)
        ->assertSee('href="'.route('locations.index').'"', false)
        ->assertSee('href="'.route('assets.index').'"', false)
        ->assertSee('href="'.route('audit.index').'"', false);
});

test('users without audit permission do not see audit data on the dashboard', function () {
    $company = createTestCompany();
    $technician = createTestUser($company, [], RoleName::Technician);

    app(AuditLogger::class)->log(
        event: 'test.private_activity',
        description: 'Actividad reservada a auditoría',
        companyId: $company->id,
    );

    $this->actingAs($technician)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Actividad reciente')
        ->assertDontSee('Eventos auditados')
        ->assertDontSee('Actividad reservada a auditoría');
});

test('users with audit permission see recent audit activity on the dashboard', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    app(AuditLogger::class)->log(
        event: 'test.visible_activity',
        description: 'Actividad visible para auditoría',
        companyId: $company->id,
    );

    $this->actingAs($companyAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Actividad reciente')
        ->assertSee('Eventos auditados')
        ->assertSee('Actividad visible para auditoría');
});

test('users from inactive companies are logged out', function () {
    $company = createTestCompany(['status' => CompanyStatus::Inactive->value]);
    $user = createTestUser($company);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('inactive users are logged out', function () {
    $user = createTestUser(createTestCompany(), ['status' => 'inactive']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});
