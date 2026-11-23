<?php

use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User;
use App\Projects\Application\DTOs\ProjectInput;
use App\Projects\Application\DTOs\ProjectTaskInput;
use App\Projects\Application\UseCases\CreateProjectTask;
use App\Projects\Application\UseCases\MoveProjectTask;
use App\Projects\Application\UseCases\UpdateProject;
use App\Projects\Application\UseCases\UpdateProjectTask;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectTaskModel;
use App\Projects\Presentation\Livewire\ProjectForm;
use App\Projects\Presentation\Livewire\ProjectIndex;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;

function makeProjectFor(
    CompanyModel $company,
    User $manager,
    array $attributes = [],
): ProjectModel {
    $project = ProjectModel::query()->create([
        'company_id' => $company->id,
        'name' => 'Proyecto de prueba '.Str::uuid(),
        'description' => 'Descripción de prueba.',
        'status' => 'active',
        'manager_id' => $manager->id,
        'created_by' => $manager->id,
        ...$attributes,
    ]);

    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $manager->id]);

    return $project;
}

test('company administrator can create a project with members and it is audited', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $technician = createTestUser($company, [], RoleName::Technician);

    $this->actingAs($admin);

    Livewire::test(ProjectForm::class)
        ->set('name', 'Proyecto demo')
        ->set('description', 'Descripción del proyecto.')
        ->set('status', 'active')
        ->set('managerId', $engineer->id)
        ->set('memberIds', [$engineer->id, $technician->id])
        ->call('save')
        ->assertHasNoErrors();

    $project = ProjectModel::query()->firstOrFail();

    expect($project->company_id)->toBe($company->id)
        ->and($project->manager_id)->toBe($engineer->id)
        ->and($project->members()->count())->toBe(2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'project.created',
        'company_id' => $company->id,
        'subject_id' => $project->id,
    ]);
});

test('service manager can create a project and becomes its manager', function () {
    $company = createTestCompany();
    $serviceManager = createTestUser($company, [], RoleName::ServiceManager);

    $this->actingAs($serviceManager);

    Livewire::test(ProjectForm::class)
        ->set('name', 'Proyecto de servicio')
        ->set('status', 'planned')
        ->set('managerId', $serviceManager->id)
        ->set('memberIds', [$serviceManager->id])
        ->call('save')
        ->assertHasNoErrors();

    $project = ProjectModel::query()->firstOrFail();

    expect($project->manager_id)->toBe($serviceManager->id)
        ->and($project->members()->count())->toBe(1);
});

test('project visibility is restricted to members, company administrators and super administrators', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $serviceManager = createTestUser($company, [], RoleName::ServiceManager);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $technician = createTestUser($company, [], RoleName::Technician);
    $outsider = createTestUser($company, [], RoleName::Technician);

    $ownedProject = makeProjectFor($company, $engineer);
    ProjectMemberModel::query()->create(['project_id' => $ownedProject->id, 'user_id' => $technician->id]);

    $otherProject = makeProjectFor($company, $admin);

    $this->actingAs($admin);
    $this->get(route('projects.show', $ownedProject->id))->assertOk();
    $this->get(route('projects.show', $otherProject->id))->assertOk();

    $this->actingAs($serviceManager);
    $this->get(route('projects.show', $ownedProject->id))->assertStatus(404);
    $this->get(route('projects.show', $otherProject->id))->assertStatus(404);

    $serviceManagerOwned = makeProjectFor($company, $serviceManager);
    $this->actingAs($serviceManager);
    $this->get(route('projects.show', $serviceManagerOwned->id))->assertOk();

    $this->actingAs($engineer);
    $this->get(route('projects.show', $ownedProject->id))->assertOk();
    $this->get(route('projects.show', $otherProject->id))->assertStatus(404);

    $this->actingAs($outsider);
    $this->get(route('projects.show', $ownedProject->id))->assertStatus(404);
});

test('project index shows only visible projects', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $serviceManager = createTestUser($company, [], RoleName::ServiceManager);
    $engineer = createTestUser($company, [], RoleName::Engineer);

    $ownedProject = makeProjectFor($company, $engineer);
    $otherProject = makeProjectFor($company, $admin);
    $serviceManagerProject = makeProjectFor($company, $serviceManager);

    $this->actingAs($admin);
    Livewire::test(ProjectIndex::class)
        ->assertSeeHtml($ownedProject->name)
        ->assertSeeHtml($otherProject->name)
        ->assertSeeHtml($serviceManagerProject->name);

    $this->actingAs($serviceManager);
    Livewire::test(ProjectIndex::class)
        ->assertSeeHtml($serviceManagerProject->name)
        ->assertDontSeeHtml($ownedProject->name)
        ->assertDontSeeHtml($otherProject->name);

    $this->actingAs($engineer);
    Livewire::test(ProjectIndex::class)
        ->assertSeeHtml($ownedProject->name)
        ->assertDontSeeHtml($otherProject->name)
        ->assertDontSeeHtml($serviceManagerProject->name);
});

test('project manager can update project details and members', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $manager = createTestUser($company, [], RoleName::Engineer);
    $newManager = createTestUser($company, [], RoleName::Technician);
    $member = createTestUser($company, [], RoleName::Technician);

    $project = makeProjectFor($company, $manager);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $member->id]);

    $this->actingAs($manager);

    $updateProject = new UpdateProject(
        app(ProjectRepository::class),
        app(UserRepository::class),
        app(AuditLogger::class),
        app(TransactionManager::class),
    );
    $updateProject->execute(
        companyId: $company->id,
        projectId: $project->id,
        input: new ProjectInput(
            name: 'Proyecto actualizado',
            description: 'Nueva descripción.',
            status: ProjectStatus::Paused,
            managerId: $newManager->id,
            startDate: null,
            dueDate: null,
            memberIds: [$newManager->id, $member->id],
        ),
        actorId: $manager->id,
    );

    $updated = ProjectModel::query()->find($project->id);

    expect($updated->name)->toBe('Proyecto actualizado')
        ->and($updated->status)->toBe('paused')
        ->and($updated->manager_id)->toBe($newManager->id)
        ->and($updated->members()->count())->toBe(2);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'project.updated',
        'company_id' => $company->id,
        'subject_id' => $project->id,
    ]);
});

test('project policies reflect membership and administration', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $technician = createTestUser($company, [], RoleName::Technician);

    $ownedProject = makeProjectFor($company, $engineer);
    ProjectMemberModel::query()->create(['project_id' => $ownedProject->id, 'user_id' => $technician->id]);
    $otherProject = makeProjectFor($company, $admin);

    expect(Gate::forUser($engineer)->allows('view', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($technician)->allows('view', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($engineer)->allows('view', $otherProject))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($engineer)->allows('create', $ownedProject))->toBeFalse()
        ->and(Gate::forUser($engineer)->allows('update', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $ownedProject))->toBeTrue()
        ->and(Gate::forUser($technician)->allows('update', $ownedProject))->toBeFalse();
});

test('task can be created for a project and linked to one ticket only', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $project = makeProjectFor($company, $admin);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $engineer->id]);

    $ticket = SupportTicketModel::query()->create([
        'company_id' => $company->id,
        'category' => TicketCategory::Hardware->value,
        'subject' => 'Ticket vinculado',
        'description' => 'Descripción del ticket.',
        'requester_name' => 'Usuario',
        'priority' => TicketPriority::Medium->value,
        'status' => 'open',
        'opened_at' => now(),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    $task = app(CreateProjectTask::class)->execute(
        companyId: $company->id,
        projectId: $project->id,
        input: new ProjectTaskInput(
            title: 'Configurar respaldo',
            description: 'Configurar copias de seguridad.',
            status: ProjectTaskStatus::Pending,
            priority: ProjectTaskPriority::High,
            assignedUserId: $engineer->id,
            dueDate: null,
            ticketId: $ticket->id,
        ),
        actorId: $admin->id,
    );

    expect($task->ticketId)->toBe($ticket->id)
        ->and($task->assignedUserId)->toBe($engineer->id);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'project_task.created',
        'company_id' => $company->id,
        'subject_id' => $task->id,
    ]);

    $this->expectException(DomainException::class);

    app(CreateProjectTask::class)->execute(
        companyId: $company->id,
        projectId: $project->id,
        input: new ProjectTaskInput(
            title: 'Tarea duplicada',
            description: null,
            status: ProjectTaskStatus::Pending,
            priority: ProjectTaskPriority::Low,
            assignedUserId: $engineer->id,
            dueDate: null,
            ticketId: $ticket->id,
        ),
        actorId: $admin->id,
    );
});

test('task ticket must belong to the project company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $project = makeProjectFor($company, $admin);

    $foreignTicket = SupportTicketModel::query()->create([
        'company_id' => $otherCompany->id,
        'category' => TicketCategory::Hardware->value,
        'subject' => 'Ticket externo',
        'description' => 'Descripción.',
        'requester_name' => 'Usuario',
        'priority' => TicketPriority::Low->value,
        'status' => 'open',
        'opened_at' => now(),
        'created_by' => createTestUser($otherCompany, [], RoleName::Engineer)->id,
    ]);

    $this->expectException(DomainException::class);

    app(CreateProjectTask::class)->execute(
        companyId: $company->id,
        projectId: $project->id,
        input: new ProjectTaskInput(
            title: 'Tarea con ticket externo',
            description: null,
            status: ProjectTaskStatus::Pending,
            priority: ProjectTaskPriority::Low,
            assignedUserId: null,
            dueDate: null,
            ticketId: $foreignTicket->id,
        ),
        actorId: $admin->id,
    );
});

test('assigned engineer can move their own task but not tasks assigned to others', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $technician = createTestUser($company, [], RoleName::Technician);
    $project = makeProjectFor($company, $admin);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $engineer->id]);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $technician->id]);

    $task = ProjectTaskModel::query()->create([
        'project_id' => $project->id,
        'title' => 'Tarea asignada',
        'description' => null,
        'status' => 'pending',
        'priority' => 'medium',
        'assigned_user_id' => $engineer->id,
        'due_date' => null,
        'ticket_id' => null,
        'sort_order' => 0,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($engineer);

    app(MoveProjectTask::class)->execute(
        taskId: $task->id,
        projectId: $project->id,
        companyId: $company->id,
        userId: $engineer->id,
        status: ProjectTaskStatus::InProgress,
        sortOrder: 0,
    );

    expect($task->refresh()->status)->toBe('in_progress');

    $this->expectException(DomainException::class);

    app(MoveProjectTask::class)->execute(
        taskId: $task->id,
        projectId: $project->id,
        companyId: $company->id,
        userId: $technician->id,
        status: ProjectTaskStatus::InProgress,
        sortOrder: 0,
    );
});

test('assigned engineer can update their task but cannot reassign or link tickets', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $otherEngineer = createTestUser($company, [], RoleName::Engineer);
    $project = makeProjectFor($company, $admin);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $engineer->id]);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $otherEngineer->id]);

    $task = ProjectTaskModel::query()->create([
        'project_id' => $project->id,
        'title' => 'Tarea asignada',
        'description' => null,
        'status' => 'pending',
        'priority' => 'medium',
        'assigned_user_id' => $engineer->id,
        'due_date' => null,
        'ticket_id' => null,
        'sort_order' => 0,
        'created_by' => $admin->id,
    ]);

    $this->actingAs($engineer);

    app(UpdateProjectTask::class)->execute(
        companyId: $company->id,
        projectId: $project->id,
        taskId: $task->id,
        input: new ProjectTaskInput(
            title: 'Tarea actualizada',
            description: 'Descripción actualizada.',
            status: ProjectTaskStatus::InProgress,
            priority: ProjectTaskPriority::Urgent,
            assignedUserId: $otherEngineer->id,
            dueDate: null,
            ticketId: 99999,
        ),
        actorId: $engineer->id,
        actorCanManageProject: false,
    );

    $updated = ProjectTaskModel::query()->find($task->id);

    expect($updated->title)->toBe('Tarea actualizada')
        ->and($updated->status)->toBe('in_progress')
        ->and($updated->priority)->toBe('urgent')
        ->and($updated->assigned_user_id)->toBe($engineer->id)
        ->and($updated->ticketId)->toBeNull();
});

test('completed project blocks task creation and movement', function () {
    $company = createTestCompany();
    $admin = createTestUser($company, [], RoleName::CompanyAdmin);
    $engineer = createTestUser($company, [], RoleName::Engineer);
    $project = makeProjectFor($company, $admin, ['status' => 'completed']);
    ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $engineer->id]);

    $this->expectException(DomainException::class);

    app(CreateProjectTask::class)->execute(
        companyId: $company->id,
        projectId: $project->id,
        input: new ProjectTaskInput(
            title: 'Tarea en proyecto cerrado',
            description: null,
            status: ProjectTaskStatus::Pending,
            priority: ProjectTaskPriority::Low,
            assignedUserId: $engineer->id,
            dueDate: null,
            ticketId: null,
        ),
        actorId: $admin->id,
    );
});
