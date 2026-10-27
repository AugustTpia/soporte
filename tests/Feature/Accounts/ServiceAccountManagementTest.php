<?php

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountAssetLinkModel;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Accounts\Presentation\Livewire\ServiceAccountForm;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Identity\Domain\Enums\RoleName;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Livewire\Livewire;

test('company administrators can create service accounts', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class)
        ->set('service', 'nextcloud')
        ->set('username', 'JPEREZ')
        ->set('instance', 'CLOUD.EMPRESA.COM')
        ->set('ownerName', 'Juan Pérez')
        ->set('responsibleUserId', (string) $companyAdmin->id)
        ->set('notes', 'Cuenta principal del usuario')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounts.index'));

    $account = ServiceAccountModel::query()->where('username', 'JPEREZ')->firstOrFail();

    expect($account->company_id)->toBe($company->id)
        ->and($account->service)->toBe('nextcloud')
        ->and($account->instance)->toBe('cloud.empresa.com')
        ->and($account->status)->toBe('active')
        ->and($account->responsible_user_id)->toBe($companyAdmin->id);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'service_account.created',
        'company_id' => $company->id,
    ]);
});

test('service account identity is unique per company service and instance', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);

    ServiceAccountModel::factory()->forCompany($company)->create([
        'service' => 'nextcloud',
        'username' => 'juan',
        'instance' => 'cloud.example.test',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class)
        ->set('service', 'nextcloud')
        ->set('username', 'juan')
        ->set('instance', 'cloud.example.test')
        ->call('save')
        ->assertHasErrors('username');

    $this->assertDatabaseCount('service_accounts', 1);
});

test('accounts can not be linked to assets from another company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);
    $otherAsset = AssetModel::factory()->forCompany($otherCompany, $otherLocation)->create();

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('linkAssetId', (string) $otherAsset->id)
        ->call('linkAsset')
        ->assertHasErrors('linkAssetId');

    $this->assertDatabaseCount('service_account_asset_links', 0);
});

test('accounts can link assets and keep the link active', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create([
        'username' => 'juan',
        'instance' => 'cloud.example.test',
    ]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create([
        'internal_code' => 'ACT-001',
        'hostname' => 'pc-001',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('linkAssetId', (string) $asset->id)
        ->call('linkAsset')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('service_account_asset_links', [
        'service_account_id' => $account->id,
        'asset_id' => $asset->id,
        'unlinked_at' => null,
        'linked_by' => $companyAdmin->id,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'service_account.asset_linked',
        'company_id' => $company->id,
    ]);

    $this->actingAs($companyAdmin)
        ->get(route('accounts.edit', $account))
        ->assertOk()
        ->assertSee('ACT-001');
});

test('accounts can not link the same asset twice while the link is active', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $asset = AssetModel::factory()->forCompany($company, $location)->create();

    ServiceAccountAssetLinkModel::query()->create([
        'service_account_id' => $account->id,
        'asset_id' => $asset->id,
        'linked_at' => now(),
        'reason' => 'Inicial',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('linkAssetId', (string) $asset->id)
        ->call('linkAsset')
        ->assertHasErrors('linkAssetId');

    $this->assertDatabaseCount('service_account_asset_links', 1);
});

test('accounts can transfer their link to a replacement asset', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $oldAsset = AssetModel::factory()->forCompany($company, $location)->create([
        'internal_code' => 'ACT-OLD',
        'hostname' => 'pc-old',
    ]);
    $newAsset = AssetModel::factory()->forCompany($company, $location)->create([
        'internal_code' => 'ACT-NEW',
        'hostname' => 'pc-new',
    ]);

    $oldLink = ServiceAccountAssetLinkModel::query()->create([
        'service_account_id' => $account->id,
        'asset_id' => $oldAsset->id,
        'linked_at' => now(),
        'reason' => 'Inicial',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('transferFromAssetId', (string) $oldAsset->id)
        ->set('transferToAssetId', (string) $newAsset->id)
        ->call('transferAsset')
        ->assertHasNoErrors();

    expect($oldLink->refresh()->unlinked_at)->not->toBeNull()
        ->and($oldLink->refresh()->unlink_reason)->toBe('Sustitución de equipo')
        ->and($oldLink->refresh()->unlinked_by)->toBe($companyAdmin->id);

    $this->assertDatabaseHas('service_account_asset_links', [
        'service_account_id' => $account->id,
        'asset_id' => $newAsset->id,
        'unlinked_at' => null,
    ]);

    $this->assertDatabaseCount('service_account_asset_links', 2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'service_account.asset_transferred',
        'company_id' => $company->id,
    ]);
});

test('accounts can be unlinked while keeping history', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $asset = AssetModel::factory()->forCompany($company, $location)->create();

    $link = ServiceAccountAssetLinkModel::query()->create([
        'service_account_id' => $account->id,
        'asset_id' => $asset->id,
        'linked_at' => now(),
        'reason' => 'Inicial',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->call('unlinkAsset', $link->id)
        ->assertHasNoErrors();

    expect($link->refresh()->unlinked_at)->not->toBeNull()
        ->and($link->refresh()->unlink_reason)->toBe('Desvinculación manual');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'service_account.asset_unlinked',
        'company_id' => $company->id,
    ]);
});

test('account history survives an asset being soft deleted', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $asset = AssetModel::factory()->forCompany($company, $location)->create();

    ServiceAccountAssetLinkModel::query()->create([
        'service_account_id' => $account->id,
        'asset_id' => $asset->id,
        'linked_at' => now(),
        'reason' => 'Inicial',
    ]);

    $asset->delete();

    $this->actingAs($companyAdmin)
        ->get(route('accounts.edit', $account))
        ->assertOk()
        ->assertSee('Equipo eliminado');
});

test('accounts can not link retired assets', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create();
    $asset = AssetModel::factory()->forCompany($company, $location)->create([
        'status' => 'retired',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('linkAssetId', (string) $asset->id)
        ->call('linkAsset')
        ->assertHasErrors('linkAssetId');

    $this->assertDatabaseCount('service_account_asset_links', 0);
});

test('suspended accounts can not create new asset links', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $account = ServiceAccountModel::factory()->forCompany($company)->create([
        'status' => 'suspended',
    ]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create();

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('linkAssetId', (string) $asset->id)
        ->call('linkAsset')
        ->assertHasErrors('linkAssetId');

    $this->assertDatabaseCount('service_account_asset_links', 0);
});

test('invalid service account status transitions are rejected', function () {
    $company = createTestCompany();
    $companyAdmin = createTestUser($company, [], RoleName::CompanyAdmin);
    $account = ServiceAccountModel::factory()->forCompany($company)->create([
        'status' => 'revoked',
    ]);

    $this->actingAs($companyAdmin);

    Livewire::test(ServiceAccountForm::class, ['account' => $account])
        ->set('status', 'active')
        ->call('save')
        ->assertHasErrors('form');

    expect($account->refresh()->status)->toBe('revoked');
});

test('auditors can view accounts but not manage them', function () {
    $company = createTestCompany();
    $auditor = createTestUser($company, [], RoleName::Auditor);

    $this->actingAs($auditor)
        ->get(route('accounts.index'))
        ->assertOk();

    $this->actingAs($auditor)
        ->get(route('accounts.create'))
        ->assertForbidden();
});

test('technicians can manage service accounts', function () {
    $company = createTestCompany();
    $technician = createTestUser($company, [], RoleName::Technician);

    $this->actingAs($technician)
        ->get(route('accounts.create'))
        ->assertOk();
});

test('super admins only manage accounts from the active company', function () {
    $superAdmin = createTestSuperAdmin();
    $company = createTestCompany();
    $otherCompany = createTestCompany();

    $account = ServiceAccountModel::factory()->forCompany($company)->create([
        'username' => 'cuenta-a',
        'instance' => 'cloud.example.test',
    ]);
    $otherAccount = ServiceAccountModel::factory()->forCompany($otherCompany)->create([
        'username' => 'cuenta-b',
        'instance' => 'cloud.example.test',
    ]);

    session(['active_company_id' => $company->id]);

    $this->actingAs($superAdmin)
        ->get(route('accounts.index'))
        ->assertOk()
        ->assertSee('cuenta-a')
        ->assertDontSee('cuenta-b');

    $this->actingAs($superAdmin)
        ->get(route('accounts.edit', $otherAccount))
        ->assertNotFound();

    $this->actingAs($superAdmin)
        ->get(route('accounts.edit', $account))
        ->assertOk();
});
