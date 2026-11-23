<?php

namespace App\Projects\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Application\DTOs\ProjectTaskInput;
use App\Projects\Application\UseCases\CreateProjectTask;
use App\Projects\Application\UseCases\MoveProjectTask;
use App\Projects\Application\UseCases\UpdateProjectTask;
use App\Projects\Domain\Entities\ProjectTask;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Projects\Domain\Enums\ProjectTaskPriority;
use App\Projects\Domain\Enums\ProjectTaskStatus;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Domain\Repositories\ProjectTaskRepository;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Proyecto')]
class ProjectShow extends Component
{
    public int $projectId;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $name = '';

    public ?string $description = null;

    public string $status = '';

    public int $managerId = 0;

    public ?string $managerName = null;

    public ?string $startDate = null;

    public ?string $dueDate = null;

    /** @var array<int, array{id: int, title: string, status: string, priority: string, assigned_user_id: int|null, assigned_user_name: string|null, due_date: string|null, ticket_id: int|null, ticket_label: string|null, sort_order: int}> */
    public array $tasks = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $members = [];

    public bool $canManage = false;

    public bool $canViewAll = false;

    public bool $taskModalOpen = false;

    public ?int $taskModalId = null;

    public string $taskModalTitle = '';

    public string $taskModalDescription = '';

    public string $taskModalStatus = 'pending';

    public string $taskModalPriority = 'medium';

    public ?int $taskModalAssignedUserId = null;

    public ?string $taskModalDueDate = null;

    public ?int $taskModalTicketId = null;

    /** @var array<int, array{id: int, name: string}> */
    public array $assignableUsers = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $ticketOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<string, string> */
    public array $priorityOptions = [];

    public function mount(int $project): void
    {
        $this->projectId = $project;

        $this->authorize('viewAny', ProjectModel::class);

        $user = auth()->user();
        $this->companyId = app(TenantContext::class)->companyId();
        $this->canViewAll = $user->isSuperAdmin() || $user->hasRole(RoleName::CompanyAdmin->value);

        if ($this->companyId === null && ! $this->canViewAll) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $project = app(ProjectRepository::class)->findVisible($this->projectId, $this->companyId, (int) $user->id, $this->canViewAll);

        if ($project === null) {
            abort(404);
        }

        $this->canManage = $user->isSuperAdmin() || $user->hasRole(RoleName::CompanyAdmin->value) || $user->id === $project->managerId;

        $company = app(CompanyRepository::class)->find($project->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->projectId = $project->id;
        $this->companyId = $project->companyId;
        $this->name = $project->name;
        $this->description = $project->description;
        $this->status = $project->status->value;
        $this->managerId = $project->managerId;
        $this->managerName = $project->managerName;
        $this->startDate = $project->startDate?->format('Y-m-d');
        $this->dueDate = $project->dueDate?->format('Y-m-d');

        $this->statusOptions = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status) => [$status->value => $status->label()])
            ->all();

        $this->members = app(ProjectRepository::class)->members($this->projectId);

        $this->loadTasks();

        $this->loadOptions();
    }

    public function loadTasks(): void
    {
        $this->tasks = array_map(fn (ProjectTask $task) => [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'assigned_user_id' => $task->assignedUserId,
            'assigned_user_name' => $task->assignedUserName,
            'due_date' => $task->dueDate?->format('Y-m-d'),
            'ticket_id' => $task->ticketId,
            'ticket_label' => $task->ticketLabel,
            'sort_order' => $task->sortOrder,
        ], app(ProjectTaskRepository::class)->boardForProject($this->projectId, $this->companyId, (int) auth()->id(), $this->canViewAll));
    }

    public function loadOptions(): void
    {
        $this->priorityOptions = collect(ProjectTaskPriority::cases())
            ->mapWithKeys(fn (ProjectTaskPriority $priority) => [$priority->value => $priority->label()])
            ->all();

        $memberIds = array_map(fn (array $member) => (int) $member['id'], $this->members);

        $users = app(UserRepository::class)->forCompany($this->companyId);

        $filteredUsers = array_values(array_filter($users, fn ($user) => in_array($user->id, $memberIds, true) && $user->isActive() && ! in_array($user->primaryRole()?->value, [RoleName::Auditor->value, RoleName::ReadOnly->value], true)));

        $this->assignableUsers = array_map(fn ($user) => ['id' => $user->id, 'name' => $user->name], $filteredUsers);

        $this->ticketOptions = array_map(
            fn ($ticket) => ['id' => $ticket->id, 'label' => "{$ticket->folio} - {$ticket->subject}"],
            app(TicketRepository::class)->forCompany($this->companyId)
        );
    }

    public function openTaskModal(?int $taskId = null): void
    {
        $user = auth()->user();

        if ($taskId === null) {
            abort_unless($this->canManage, 403);

            $this->taskModalId = null;
            $this->taskModalTitle = '';
            $this->taskModalDescription = '';
            $this->taskModalStatus = 'pending';
            $this->taskModalPriority = 'medium';
            $this->taskModalAssignedUserId = null;
            $this->taskModalDueDate = null;
            $this->taskModalTicketId = null;
            $this->taskModalOpen = true;

            return;
        }

        $task = app(ProjectTaskRepository::class)->findVisible($taskId, $this->projectId, $this->companyId, (int) $user->id, $this->canViewAll);

        if ($task === null) {
            abort(404);
        }

        abort_unless($this->canManage || $task->assignedUserId === (int) $user->id, 403);

        $this->taskModalId = $task->id;
        $this->taskModalTitle = $task->title;
        $this->taskModalDescription = $task->description ?? '';
        $this->taskModalStatus = $task->status->value;
        $this->taskModalPriority = $task->priority->value;
        $this->taskModalAssignedUserId = $task->assignedUserId;
        $this->taskModalDueDate = $task->dueDate?->format('Y-m-d');
        $this->taskModalTicketId = $task->ticketId;
        $this->taskModalOpen = true;
    }

    public function saveTask(): void
    {
        $user = auth()->user();
        $actorId = (int) $user->id;

        $validated = $this->validate([
            'taskModalTitle' => ['required', 'string', 'max:200'],
            'taskModalDescription' => ['nullable', 'string', 'max:5000'],
            'taskModalStatus' => ['required', Rule::enum(ProjectTaskStatus::class)],
            'taskModalPriority' => ['required', Rule::enum(ProjectTaskPriority::class)],
            'taskModalDueDate' => ['nullable', 'date'],
        ]);

        if ($this->taskModalId === null) {
            abort_unless($this->canManage, 403);

            $input = new ProjectTaskInput(
                title: $validated['taskModalTitle'],
                description: $this->clean($validated['taskModalDescription'] ?? null),
                status: ProjectTaskStatus::from($validated['taskModalStatus']),
                priority: ProjectTaskPriority::from($validated['taskModalPriority']),
                assignedUserId: $this->taskModalAssignedUserId,
                dueDate: $this->taskModalDueDate !== null ? new \DateTimeImmutable($this->taskModalDueDate) : null,
                ticketId: $this->taskModalTicketId,
            );

            try {
                app(CreateProjectTask::class)->execute(
                    companyId: $this->companyId,
                    projectId: $this->projectId,
                    input: $input,
                    actorId: $actorId,
                );

                Flux::toast(variant: 'success', text: 'Tarea creada correctamente.');
            } catch (DomainException $exception) {
                $this->addError('taskModalForm', $exception->getMessage());
                Flux::toast(variant: 'danger', text: $exception->getMessage());

                return;
            }
        } else {
            $task = app(ProjectTaskRepository::class)->findVisible($this->taskModalId, $this->projectId, $this->companyId, $actorId, $this->canViewAll);

            if ($task === null) {
                abort(404);
            }

            abort_unless($this->canManage || $task->assignedUserId === $actorId, 403);

            if (! $this->canManage) {
                $this->taskModalAssignedUserId = $task->assignedUserId;
                $this->taskModalTicketId = $task->ticketId;
            } elseif ($this->taskModalTicketId !== null) {
                $ticket = app(TicketRepository::class)->find($this->taskModalTicketId, $this->companyId);

                if ($ticket === null) {
                    $this->addError('taskModalTicketId', 'El ticket seleccionado no pertenece a esta empresa.');
                    Flux::toast(variant: 'danger', text: 'El ticket seleccionado no pertenece a esta empresa.');

                    return;
                }

                if (app(ProjectTaskRepository::class)->findByTicket($this->companyId, $this->taskModalTicketId, $this->taskModalId) !== null) {
                    $this->addError('taskModalTicketId', 'El ticket ya está vinculado a otra tarea.');
                    Flux::toast(variant: 'danger', text: 'El ticket ya está vinculado a otra tarea.');

                    return;
                }
            }

            $input = new ProjectTaskInput(
                title: $validated['taskModalTitle'],
                description: $this->clean($validated['taskModalDescription'] ?? null),
                status: ProjectTaskStatus::from($validated['taskModalStatus']),
                priority: ProjectTaskPriority::from($validated['taskModalPriority']),
                assignedUserId: $this->taskModalAssignedUserId,
                dueDate: $this->taskModalDueDate !== null ? new \DateTimeImmutable($this->taskModalDueDate) : null,
                ticketId: $this->taskModalTicketId,
            );

            try {
                app(UpdateProjectTask::class)->execute(
                    companyId: $this->companyId,
                    projectId: $this->projectId,
                    taskId: $this->taskModalId,
                    input: $input,
                    actorId: $actorId,
                    actorCanManageProject: $this->canManage,
                );

                Flux::toast(variant: 'success', text: 'Tarea actualizada correctamente.');
            } catch (DomainException $exception) {
                $this->addError('taskModalForm', $exception->getMessage());
                Flux::toast(variant: 'danger', text: $exception->getMessage());

                return;
            }
        }

        $this->taskModalOpen = false;
        $this->loadTasks();
    }

    public function moveTask(int $taskId, string $status, int $sortOrder): void
    {
        $user = auth()->user();
        $actorId = (int) $user->id;

        $task = app(ProjectTaskRepository::class)->findVisible($taskId, $this->projectId, $this->companyId, $actorId, $this->canViewAll);

        if ($task === null) {
            abort(404);
        }

        abort_unless($this->canManage || $task->assignedUserId === $actorId, 403);

        try {
            app(MoveProjectTask::class)->execute(
                taskId: $taskId,
                projectId: $this->projectId,
                companyId: $this->companyId,
                userId: $actorId,
                status: ProjectTaskStatus::from($status),
                sortOrder: max(0, (int) $sortOrder),
            );

            Flux::toast(variant: 'success', text: 'Tarea movida correctamente.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }

        $this->loadTasks();
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function render(): View
    {
        $canViewAll = $this->canViewAll;
        $user = auth()->user();

        $columns = [
            'pending' => 'Pendiente',
            'in_progress' => 'En proceso',
            'blocked' => 'Bloqueada',
            'completed' => 'Completada',
        ];

        $boards = [];

        foreach ($columns as $status => $label) {
            $boards[$status] = [
                'label' => $label,
                'tasks' => array_values(array_filter($this->tasks, fn (array $task) => $task['status'] === $status)),
            ];
        }

        return view('projects.livewire.show', [
            'project' => $this,
            'boards' => $boards,
            'canManage' => $this->canManage,
            'canViewAll' => $canViewAll,
            'user' => $user,
        ]);
    }
}
