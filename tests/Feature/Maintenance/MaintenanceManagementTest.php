<?php

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionForm;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionIndex;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionShow;
use App\Maintenance\Presentation\Livewire\MaintenancePlanForm;
use App\Maintenance\Presentation\Livewire\MaintenancePlanShow;
use Livewire\Livewire;

function makeMaintenancePlanFor(
    CompanyModel $company,
    User $actor,
    User $technician,
    AssetModel $asset,
    array $attributes = [],
): MaintenancePlanModel {
    $plan = MaintenancePlanModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'asset',
        'asset_id' => $asset->id,
        'target_label' => $asset->internal_code,
        'title' => 'Mantenimiento preventivo',
        'description' => 'Revisión programada del equipo.',
        'interval_value' => 1,
        'interval_unit' => 'months',
        'next_due_at' => now()->subDay(),
        'assigned_user_id' => $technician->id,
        'status' => 'active',
        'created_by' => $actor->id,
        ...$attributes,
    ]);

    $plan->checklistItems()->createMany([
        ['label' => 'Revisar limpieza interna', 'is_required' => true, 'sort_order' => 0],
        ['label' => 'Actualizar sistema operativo', 'is_required' => true, 'sort_order' => 1],
    ]);

    return $plan;
}

function makeMaintenanceExecutionFor(
    CompanyModel $company,
    User $actor,
    User $technician,
    array $attributes = [],
): MaintenanceExecutionModel {
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $technician->id,
        'created_by' => $actor->id,
        ...$attributes,
    ]);

    $execution->checklistItems()->create([
        'label' => 'Verificar funcionamiento',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    return $execution;
}

test('company administrators can create maintenance plans with a checklist', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create();

    $this->actingAs($admin);

    $component = Livewire::test(MaintenancePlanForm::class)
        ->set('targetType', 'asset')
        ->set('assetId', (string) $asset->id)
        ->set('title', 'Mantenimiento trimestral')
        ->set('description', 'Revisión de hardware y respaldo.')
        ->set('intervalValue', 3)
        ->set('intervalUnit', 'months')
        ->set('nextDueAt', now()->addDays(10)->format('Y-m-d\TH:i'))
        ->set('assignedUserId', (string) $technician->id)
        ->set('checklist', [
            ['label' => 'Revisar ventiladores', 'is_required' => true],
            ['label' => 'Limpiar polvo', 'is_required' => false],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $plan = MaintenancePlanModel::query()->firstOrFail();
    $component->assertRedirect(route('maintenance.plans.show', $plan->id));

    expect($plan->company_id)->toBe($company->id)
        ->and($plan->asset_id)->toBe($asset->id)
        ->and($plan->target_type)->toBe('asset')
        ->and($plan->assigned_user_id)->toBe($technician->id)
        ->and($plan->interval_value)->toBe(3)
        ->and($plan->interval_unit)->toBe('months')
        ->and($plan->checklistItems()->count())->toBe(2);

    $this->assertDatabaseHas('maintenance_plan_checklist_items', [
        'maintenance_plan_id' => $plan->id,
        'label' => 'Revisar ventiladores',
        'is_required' => true,
        'sort_order' => 0,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_plan.created',
        'company_id' => $company->id,
        'subject_id' => $plan->id,
    ]);

    Livewire::test(MaintenancePlanShow::class, ['plan' => $plan->id])
        ->assertOk()
        ->assertSee('Mantenimiento trimestral')
        ->assertSee('Revisar ventiladores')
        ->assertSee('Programar ejecución');
});

test('maintenance plans can only target assets from the active company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);
    $foreignAsset = AssetModel::factory()->forCompany($otherCompany, $otherLocation)->create();

    $this->actingAs($admin);

    Livewire::test(MaintenancePlanForm::class)
        ->set('targetType', 'asset')
        ->set('assetId', (string) $foreignAsset->id)
        ->set('title', 'Plan inválido')
        ->set('nextDueAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('assignedUserId', (string) $technician->id)
        ->call('save')
        ->assertHasErrors('assetId');

    $this->assertDatabaseCount('maintenance_plans', 0);
});

test('scheduled plan executions copy the checklist and advance the plan when completed', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create();
    $plan = makeMaintenancePlanFor($company, $admin, $technician, $asset);
    $previousDue = $plan->next_due_at;

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionForm::class, ['plan' => $plan->id])
        ->set('scheduledAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('technicianId', (string) $technician->id)
        ->call('save')
        ->assertHasNoErrors();

    $execution = MaintenanceExecutionModel::query()->firstOrFail();

    expect($execution->maintenance_plan_id)->toBe($plan->id)
        ->and($execution->type)->toBe('preventive')
        ->and($execution->status)->toBe('scheduled')
        ->and($execution->checklistItems()->count())->toBe(2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_execution.scheduled',
        'company_id' => $company->id,
        'subject_id' => $execution->id,
    ]);

    $requiredItems = $execution->checklistItems()->where('is_required', true)->get();

    $component = Livewire::test(MaintenanceExecutionShow::class, ['execution' => $execution->id])
        ->call('start')
        ->assertHasNoErrors();

    foreach ($requiredItems as $item) {
        $component->call('toggleChecklist', $item->id)->assertHasNoErrors();
    }

    $component->call('complete')->assertHasNoErrors();

    expect($execution->refresh()->status)->toBe('completed')
        ->and($execution->completed_at)->not->toBeNull()
        ->and($plan->refresh()->last_completed_at)->not->toBeNull()
        ->and($plan->next_due_at->greaterThan($previousDue))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_execution.completed',
        'company_id' => $company->id,
        'subject_id' => $execution->id,
    ]);
});

test('executions can not be completed with pending required checklist items', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $execution = makeMaintenanceExecutionFor($company, $admin, $technician);

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionShow::class, ['execution' => $execution->id])
        ->call('start')
        ->call('complete');

    expect($execution->refresh()->status)->toBe('in_progress');

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'maintenance_execution.completed',
        'subject_id' => $execution->id,
    ]);
});

test('executions can be cancelled with a reason and the action is audited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $execution = makeMaintenanceExecutionFor($company, $admin, $technician);

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionShow::class, ['execution' => $execution->id])
        ->call('cancel')
        ->assertHasErrors('cancellationReason');

    expect($execution->refresh()->status)->toBe('scheduled');

    Livewire::test(MaintenanceExecutionShow::class, ['execution' => $execution->id])
        ->set('cancellationReason', 'El equipo fue retirado de la sede.')
        ->call('cancel')
        ->assertHasNoErrors();

    expect($execution->refresh()->status)->toBe('cancelled')
        ->and($execution->cancellation_reason)->toBe('El equipo fue retirado de la sede.')
        ->and($execution->cancelled_at)->not->toBeNull();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_execution.cancelled',
        'company_id' => $company->id,
        'subject_id' => $execution->id,
    ]);
});

test('maintenance records are isolated by company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);
    $otherTechnician = createTestUser($otherCompany, [], RoleName::Technician);
    $otherLocation = LocationModel::factory()->create(['company_id' => $otherCompany->id]);
    $otherAsset = AssetModel::factory()->forCompany($otherCompany, $otherLocation)->create();
    $foreignPlan = makeMaintenancePlanFor($otherCompany, $otherAdmin, $otherTechnician, $otherAsset);
    $foreignExecution = makeMaintenanceExecutionFor($otherCompany, $otherAdmin, $otherTechnician);

    $this->actingAs($admin);

    Livewire::test(MaintenancePlanShow::class, ['plan' => $foreignPlan->id])
        ->assertStatus(404);

    Livewire::test(MaintenanceExecutionShow::class, ['execution' => $foreignExecution->id])
        ->assertStatus(404);

    $this->get(route('maintenance.executions.index'))
        ->assertOk()
        ->assertDontSee($foreignExecution->target_label);
});

test('read-only users can not create or schedule maintenance', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $auditor = createTestUser($company, [], RoleName::Auditor);
    $technician = createTestUser($company, [], RoleName::Technician);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create();
    $plan = makeMaintenancePlanFor($company, $admin, $technician, $asset);

    $this->actingAs($auditor);

    Livewire::test(MaintenancePlanForm::class)->assertStatus(403);
    Livewire::test(MaintenanceExecutionForm::class, ['plan' => $plan->id])->assertStatus(403);

    $this->get(route('maintenance.index'))
        ->assertOk()
        ->assertSee('Mantenimientos');

    $this->get(route('maintenance.executions.index'))
        ->assertOk();
});

test('maintenance execution index filters by status and plan', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $technician = createTestUser($company, [], RoleName::Technician);
    $location = LocationModel::factory()->create(['company_id' => $company->id]);
    $asset = AssetModel::factory()->forCompany($company, $location)->create();
    $plan = makeMaintenancePlanFor($company, $admin, $technician, $asset);
    $scheduled = makeMaintenanceExecutionFor($company, $admin, $technician, [
        'maintenance_plan_id' => $plan->id,
        'type' => 'preventive',
        'status' => 'scheduled',
        'target_type' => 'asset',
        'asset_id' => $asset->id,
        'target_label' => $asset->internal_code,
    ]);
    $completed = makeMaintenanceExecutionFor($company, $admin, $technician, [
        'status' => 'completed',
        'completed_at' => now(),
        'target_label' => 'Correctivo cerrado',
    ]);
    $completed->checklistItems()->update([
        'is_completed' => true,
        'completed_at' => now(),
        'completed_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionIndex::class)
        ->set('status', 'completed')
        ->assertSee($completed->target_label)
        ->assertDontSee($scheduled->target_label);

    Livewire::test(MaintenanceExecutionIndex::class)
        ->set('plan', (string) $plan->id)
        ->assertSee($scheduled->target_label)
        ->assertDontSee($completed->target_label);
});
