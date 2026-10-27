<?php

use App\Identity\Application\DTOs\UserInput;
use App\Identity\Application\UseCases\CreateUser;
use App\Identity\Application\UseCases\ToggleUserStatus;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Identity\Presentation\Livewire\Users\UserForm;
use App\Identity\Presentation\Livewire\Users\UserIndex;
use App\Shared\Domain\Exceptions\DomainException;
use Livewire\Livewire;

test('company administrators can create users in their company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(UserForm::class)
        ->set('name', 'Técnico Nuevo')
        ->set('email', 'tecnico@example.test')
        ->set('jobTitle', 'Técnico de campo')
        ->set('phone', '5512345678')
        ->set('password', 'Password123')
        ->set('password_confirmation', 'Password123')
        ->set('roles', [RoleName::Technician->value])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'tecnico@example.test')->firstOrFail();

    expect($user->company_id)->toBe($company->id);
    expect($user->hasRole(RoleName::Technician->value))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'user.created',
        'company_id' => $company->id,
        'subject_id' => $user->id,
    ]);
});

test('company administrators can not assign privileged roles', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $input = new UserInput(
        name: 'Super intruso',
        email: 'super@example.test',
        companyId: $company->id,
        password: 'Password123',
        roles: [RoleName::SuperAdmin->value],
    );

    expect(fn () => app(CreateUser::class)->execute(
        $companyAdmin->id,
        false,
        $company->id,
        $input,
    ))->toThrow(DomainException::class);
});

test('company administrators can not move users to another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $input = new UserInput(
        name: 'Usuario Seguro',
        email: 'seguro@example.test',
        companyId: $otherCompany->id,
        password: 'Password123',
        roles: [RoleName::Technician->value],
    );

    $user = app(CreateUser::class)->execute(
        $companyAdmin->id,
        false,
        $company->id,
        $input,
    );

    expect($user->companyId)->toBe($company->id);
});

test('company administrators can not edit users from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherUser = createTestUser($otherCompany, [], RoleName::Technician);

    $this->actingAs($companyAdmin)
        ->get(route('users.edit', $otherUser))
        ->assertForbidden();
});

test('users can not change their own status', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    expect(fn () => app(ToggleUserStatus::class)->execute(
        $companyAdmin->id,
        false,
        $company->id,
        $companyAdmin->id,
    ))->toThrow(DomainException::class);
});

test('super admins start with the active company preselected', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin);
    session(['active_company_id' => $company->id]);

    Livewire::test(UserForm::class)
        ->assertSet('companyId', (string) $company->id);
});

test('super admins can create users in a selected company', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin);

    Livewire::test(UserForm::class)
        ->set('companyId', (string) $company->id)
        ->set('name', 'Administrador Empresa')
        ->set('email', 'admin.empresa@example.test')
        ->set('password', 'Password123')
        ->set('password_confirmation', 'Password123')
        ->set('roles', [RoleName::CompanyAdmin->value])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'admin.empresa@example.test')->firstOrFail();

    expect($user->company_id)->toBe($company->id);
    expect($user->hasRole(RoleName::CompanyAdmin->value))->toBeTrue();
});

test('company administrators only see users from their company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    createTestUser($company, ['name' => 'Visible Company']);
    createTestUser($otherCompany, ['name' => 'Hidden Company']);

    $this->actingAs($companyAdmin);

    Livewire::test(UserIndex::class)
        ->assertSee('Visible Company')
        ->assertDontSee('Hidden Company');
});
