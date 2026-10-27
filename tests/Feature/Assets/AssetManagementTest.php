<?php

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetAssignmentModel;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Assets\Presentation\Livewire\AssetForm;
use App\Identity\Domain\Enums\RoleName;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Livewire\Livewire;

test('company administrators can create assets with an initial assignment', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('hostname', 'PC-RECEPCION-01')
        ->set('category', 'desktop')
        ->set('brand', 'Dell')
        ->set('model', 'OptiPlex 7010')
        ->set('serialNumber', 'ABC123456')
        ->set('locationId', (string) $location->id)
        ->set('assignedUserId', (string) $companyAdmin->id)
        ->set('operatingSystem', 'Windows 11 Pro')
        ->set('ipAddress', '192.168.1.25')
        ->set('purchaseDate', '2025-01-15')
        ->set('warrantyUntil', '2027-01-15')
        ->set('notes', 'Equipo de recepción')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('assets.index'));

    $asset = AssetModel::query()->where('internal_code', 'ACT-001')->firstOrFail();

    expect($asset->company_id)->toBe($company->id)
        ->and($asset->hostname)->toBe('pc-recepcion-01')
        ->and($asset->location_id)->toBe($location->id)
        ->and($asset->assigned_user_id)->toBe($companyAdmin->id)
        ->and($asset->status)->toBe('active');

    $this->assertDatabaseHas('asset_assignments', [
        'asset_id' => $asset->id,
        'location_id' => $location->id,
        'assigned_user_id' => $companyAdmin->id,
        'released_at' => null,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'asset.created',
        'company_id' => $company->id,
    ]);
});

test('assets require a location', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('category', 'desktop')
        ->set('locationId', '')
        ->call('save')
        ->assertHasErrors(['locationId' => 'required']);

    $this->assertDatabaseCount('assets', 0);
});

test('assets can not be assigned to a location from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('category', 'desktop')
        ->set('locationId', (string) $otherLocation->id)
        ->call('save')
        ->assertHasErrors('locationId');
});

test('assets can not use inactive locations', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $inactiveLocation = LocationModel::factory()->create([
        'company_id' => $company->id,
        'is_active' => false,
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('category', 'desktop')
        ->set('locationId', (string) $inactiveLocation->id)
        ->call('save')
        ->assertHasErrors('form');

    $this->assertDatabaseCount('assets', 0);
});

test('editing an asset can preserve its current inactive location', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $inactiveLocation = LocationModel::factory()->create([
        'company_id' => $company->id,
        'is_active' => false,
    ]);
    $asset = AssetModel::factory()->forCompany($company, $inactiveLocation)->create([
        'internal_code' => 'ACT-001',
        'hostname' => 'pc-001',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class, ['asset' => $asset])
        ->assertSet('locationId', (string) $inactiveLocation->id)
        ->set('brand', 'Dell')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('assets.index'));

    expect($asset->refresh()->brand)->toBe('Dell');
});

test('asset internal code and hostname are unique per company', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);

    AssetModel::factory()->forCompany($company, $location)->create([
        'internal_code' => 'ACT-001',
        'hostname' => 'pc-001',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('hostname', 'pc-002')
        ->set('category', 'desktop')
        ->set('locationId', (string) $location->id)
        ->call('save')
        ->assertHasErrors('internalCode');

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-002')
        ->set('hostname', 'pc-001')
        ->set('category', 'desktop')
        ->set('locationId', (string) $location->id)
        ->call('save')
        ->assertHasErrors('hostname');
});

test('assets reject invalid ip addresses', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class)
        ->set('internalCode', 'ACT-001')
        ->set('category', 'desktop')
        ->set('locationId', (string) $location->id)
        ->set('ipAddress', 'not-an-ip')
        ->call('save')
        ->assertHasErrors('ipAddress');
});

test('company administrators can not edit assets from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);
    $asset = AssetModel::factory()->forCompany($otherCompany, $otherLocation)->create();

    $this->actingAs($companyAdmin)
        ->get(route('assets.edit', $asset))
        ->assertForbidden();
});

test('transferring an asset keeps its assignment history', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $firstLocation = LocationModel::factory()->create(['company_id' => $company->id, 'name' => 'Matriz']);
    $secondLocation = LocationModel::factory()->create(['company_id' => $company->id, 'name' => 'Sucursal Norte']);
    $asset = AssetModel::factory()->forCompany($company, $firstLocation)->create([
        'assigned_user_id' => $companyAdmin->id,
        'internal_code' => 'ACT-001',
        'hostname' => 'pc-001',
    ]);

    $initialAssignment = AssetAssignmentModel::query()->create([
        'asset_id' => $asset->id,
        'location_id' => $firstLocation->id,
        'assigned_user_id' => $companyAdmin->id,
        'assigned_at' => now(),
        'created_by' => $companyAdmin->id,
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class, ['asset' => $asset])
        ->set('locationId', (string) $secondLocation->id)
        ->set('assignedUserId', (string) $companyAdmin->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('assets.index'));

    expect($asset->refresh()->location_id)->toBe($secondLocation->id)
        ->and($initialAssignment->refresh()->released_at)->not->toBeNull();

    $this->assertDatabaseHas('asset_assignments', [
        'asset_id' => $asset->id,
        'location_id' => $secondLocation->id,
        'assigned_user_id' => $companyAdmin->id,
        'released_at' => null,
    ]);

    $this->assertDatabaseCount('asset_assignments', 2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'asset.transferred',
        'company_id' => $company->id,
    ]);

    $this->actingAs($companyAdmin)
        ->get(route('assets.edit', $asset))
        ->assertOk()
        ->assertSee('Matriz')
        ->assertSee('Sucursal Norte');
});

test('invalid asset status transitions are rejected', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create([
        'status' => 'retired',
        'hostname' => 'pc-001',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(AssetForm::class, ['asset' => $asset])
        ->set('status', 'active')
        ->call('save')
        ->assertHasErrors('form');

    expect($asset->refresh()->status)->toBe('retired');
});

test('auditors can view assets but not manage them', function () {
    $company = createTestCompany();
    $auditor = createTestUser($company, [], RoleName::Auditor);

    $this->actingAs($auditor)
        ->get(route('assets.index'))
        ->assertOk();

    $this->actingAs($auditor)
        ->get(route('assets.create'))
        ->assertForbidden();
});

test('technicians can manage assets', function () {
    $company = createTestCompany();
    $technician = createTestUser($company, [], RoleName::Technician);

    $this->actingAs($technician)
        ->get(route('assets.create'))
        ->assertOk();
});

test('super admins only manage assets from the active company', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);

    $asset = AssetModel::factory()->forCompany($company, $location)->create([
        'internal_code' => 'A-001',
        'hostname' => 'a-001',
    ]);
    $otherAsset = AssetModel::factory()->forCompany($otherCompany, $otherLocation)->create([
        'internal_code' => 'B-001',
        'hostname' => 'b-001',
    ]);

    session(['active_company_id' => $company->id]);

    $this->actingAs($superAdmin)
        ->get(route('assets.index'))
        ->assertOk()
        ->assertSee('A-001')
        ->assertDontSee('B-001');

    $this->actingAs($superAdmin)
        ->get(route('assets.edit', $otherAsset))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('assets.edit', $asset))
        ->assertOk();
});
