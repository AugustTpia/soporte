<?php

use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Departments\Presentation\Livewire\DepartmentForm;
use App\Departments\Presentation\Livewire\DepartmentIndex;
use App\Identity\Domain\Enums\RoleName;
use Livewire\Livewire;

test('company administrators can create departments for their company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(DepartmentForm::class)
        ->set('name', 'Soporte técnico')
        ->set('description', 'Atención a usuarios')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseHas('departments', [
        'company_id' => $company->id,
        'name' => 'Soporte técnico',
        'slug' => 'soporte-tecnico',
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'department.created',
        'company_id' => $company->id,
    ]);
});

test('duplicate department names are rejected per company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    DepartmentModel::factory()->create([
        'company_id' => $company->id,
        'name' => 'Soporte',
        'slug' => 'soporte',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(DepartmentForm::class)
        ->set('name', 'Soporte')
        ->call('save')
        ->assertHasErrors('form');
});

test('company administrators can not edit departments from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $department = DepartmentModel::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $this->actingAs($companyAdmin)
        ->get(route('departments.edit', $department))
        ->assertForbidden();
});

test('company administrators can toggle department status', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $department = DepartmentModel::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(DepartmentIndex::class)
        ->call('toggleStatus', $department->id)
        ->assertHasNoErrors();

    expect($department->refresh()->is_active)->toBeFalse();
});

test('departments require an active tenant', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin)
        ->get(route('departments.index'))
        ->assertRedirect(route('companies.index'));
});
