<?php

use App\Identity\Domain\Enums\RoleName;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionAttachmentModel;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Maintenance\Presentation\Livewire\MaintenanceExecutionAttachments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

function makeExecutionAttachmentFor(User $admin, MaintenanceExecutionModel $execution): MaintenanceExecutionAttachmentModel
{
    Livewire::actingAs($admin)
        ->test(MaintenanceExecutionAttachments::class, ['executionId' => $execution->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    return MaintenanceExecutionAttachmentModel::query()->firstOrFail();
}

test('maintenance managers can attach private evidence to an execution', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $admin->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionAttachments::class, ['executionId' => $execution->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertHasNoErrors();

    $attachment = MaintenanceExecutionAttachmentModel::query()->firstOrFail();

    expect($attachment->company_id)->toBe($company->id)
        ->and($attachment->maintenance_execution_id)->toBe($execution->id)
        ->and($attachment->original_name)->toBe('evidencia.pdf')
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->uploaded_by)->toBe($admin->id)
        ->and($attachment->size_bytes)->toBeGreaterThan(0);

    Storage::disk('local')->assertExists($attachment->path);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_execution_attachment.uploaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('maintenance attachments reject unsafe files', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $admin->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(MaintenanceExecutionAttachments::class, ['executionId' => $execution->id])
        ->set('file', UploadedFile::fake()->create('script.php', 10, 'text/x-php'))
        ->call('upload')
        ->assertHasErrors('file');

    $this->assertDatabaseCount('maintenance_execution_attachments', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

test('read-only users can not upload maintenance attachments', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $auditor = createTestUser($company, [], RoleName::Auditor);
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $admin->id,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($auditor);

    Livewire::test(MaintenanceExecutionAttachments::class, ['executionId' => $execution->id])
        ->set('file', UploadedFile::fake()->create('evidencia.pdf', 120, 'application/pdf'))
        ->call('upload')
        ->assertStatus(403);
});

test('maintenance attachments can be downloaded by authorized viewers and the access is audited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $admin->id,
        'created_by' => $admin->id,
    ]);
    $attachment = makeExecutionAttachmentFor($admin, $execution);

    $this->actingAs($engineer);

    $this->get(route('maintenance.executions.attachments.download', [
        'execution' => $execution->id,
        'attachment' => $attachment->id,
    ]))
        ->assertOk()
        ->assertDownload('evidencia.pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'maintenance_execution_attachment.downloaded',
        'company_id' => $company->id,
        'subject_id' => $attachment->id,
    ]);
});

test('maintenance attachment downloads are isolated by company and execution', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $otherAdmin = createTestUser($otherCompany, [], RoleName::CompanyAdmin);
    $execution = MaintenanceExecutionModel::query()->create([
        'company_id' => $company->id,
        'target_type' => 'company',
        'target_label' => $company->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $admin->id,
        'created_by' => $admin->id,
    ]);
    $otherExecution = MaintenanceExecutionModel::query()->create([
        'company_id' => $otherCompany->id,
        'target_type' => 'company',
        'target_label' => $otherCompany->name,
        'type' => 'corrective',
        'status' => 'scheduled',
        'scheduled_at' => now()->addDay(),
        'technician_id' => $otherAdmin->id,
        'created_by' => $otherAdmin->id,
    ]);
    $foreignAttachment = makeExecutionAttachmentFor($otherAdmin, $otherExecution);

    $this->actingAs($admin);

    $this->get(route('maintenance.executions.attachments.download', [
        'execution' => $execution->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();

    $this->get(route('maintenance.executions.attachments.download', [
        'execution' => $otherExecution->id,
        'attachment' => $foreignAttachment->id,
    ]))->assertNotFound();
});
