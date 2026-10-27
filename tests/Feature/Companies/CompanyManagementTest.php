<?php

use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Companies\Presentation\Livewire\Companies\CompanyForm;
use App\Companies\Presentation\Livewire\Companies\CompanyIndex;
use App\Companies\Presentation\Livewire\Companies\CompanySwitcher;
use App\Identity\Domain\Enums\RoleName;
use App\Shared\Application\Contracts\TenantContext;
use Livewire\Livewire;

test('super admins can view the companies index', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany(['name' => 'Empresa Visible']);

    $this->actingAs($superAdmin)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Empresa Visible');
});

test('company administrators can not manage companies', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin)
        ->get(route('companies.index'))
        ->assertForbidden();
});

test('super admins can create a company and it becomes the active tenant', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin);

    Livewire::test(CompanyForm::class)
        ->set('name', 'Nueva Empresa')
        ->set('legalName', 'Nueva Empresa S.A. de C.V.')
        ->set('taxId', 'NEM123456ABC')
        ->set('email', 'contacto@nueva.test')
        ->set('phone', '5512345678')
        ->set('timezone', 'America/Mexico_City')
        ->set('locale', 'es_MX')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $company = CompanyModel::query()->where('name', 'Nueva Empresa')->firstOrFail();

    expect($company->status)->toBe(CompanyStatus::Active->value);
    expect(session('active_company_id'))->toBe($company->id);
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'company.created',
        'company_id' => $company->id,
        'actor_id' => $superAdmin->id,
    ]);
});

test('super admins can deactivate a company', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin);
    session(['active_company_id' => $company->id]);

    Livewire::test(CompanyIndex::class)
        ->call('toggleStatus', $company->id)
        ->assertHasNoErrors();

    expect($company->refresh()->status)->toBe(CompanyStatus::Inactive->value);
    expect(session('active_company_id'))->toBeNull();
    $this->assertDatabaseHas('audit_logs', [
        'event' => 'company.status_changed',
        'subject_id' => $company->id,
    ]);
});

test('super admins can switch the active tenant from the company selector', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin);

    Livewire::test(CompanySwitcher::class)
        ->set('selectedCompany', (string) $company->id)
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(session('active_company_id'))->toBe($company->id);
    expect(app(TenantContext::class)->companyId())->toBe($company->id);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('users.index').'"', false);
});

test('super admins can clear the active tenant from the company selector', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();

    $this->actingAs($superAdmin);
    session(['active_company_id' => $company->id]);

    Livewire::test(CompanySwitcher::class)
        ->set('selectedCompany', '')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(session('active_company_id'))->toBeNull();
    expect(app(TenantContext::class)->companyId())->toBeNull();
});

test('the company selector rejects inactive companies', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany(['status' => CompanyStatus::Inactive->value]);

    $this->actingAs($superAdmin);

    Livewire::test(CompanySwitcher::class)
        ->set('selectedCompany', (string) $company->id)
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect(session('active_company_id'))->toBeNull();
});

test('invalid active tenant sessions are cleared for super admins', function () {
    $superAdmin = createTestSuperAdmin();

    $this->actingAs($superAdmin)
        ->withSession(['active_company_id' => 999999])
        ->get(route('dashboard'))
        ->assertOk();

    expect(session('active_company_id'))->toBeNull();
});
