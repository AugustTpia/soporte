<?php

namespace App\Projects\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Projects\Application\DTOs\ProjectInput;
use App\Projects\Application\UseCases\CreateProject;
use App\Projects\Application\UseCases\UpdateProject;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Projects\Domain\Repositories\ProjectRepository;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Proyecto')]
class ProjectForm extends Component
{
    public ?int $projectId = null;

    public ?int $companyId = null;

    public ?int $selectCompanyId = null;

    public string $companyName = '';

    public string $name = '';

    public string $description = '';

    public string $status = 'planned';

    public ?int $managerId = null;

    public ?string $startDate = null;

    public ?string $dueDate = null;

    /** @var array<int, int> */
    public array $memberIds = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $companyOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $userOptions = [];

    public function mount(?ProjectModel $project = null): void
    {
        $user = auth()->user();
        $this->companyId = app(TenantContext::class)->companyId();
        $canViewAll = $user->isSuperAdmin() || $user->hasRole(RoleName::CompanyAdmin->value);

        if ($this->companyId === null) {
            if (! $canViewAll) {
                $this->redirectRoute('companies.index', navigate: true);

                return;
            }

            $this->companyOptions = CompanyModel::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
                ->all();
        } else {
            $company = app(CompanyRepository::class)->find($this->companyId);
            $this->companyName = $company !== null ? $company->name : '';
        }

        $this->statusOptions = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status) => [$status->value => $status->label()])
            ->all();

        $this->loadUserOptions();

        if ($project === null) {
            $this->authorize('create', ProjectModel::class);
            $this->status = ProjectStatus::Planned->value;
            $this->memberIds = [];

            return;
        }

        $this->authorize('update', $project);

        $effectiveCompanyId = $this->companyId ?? $this->selectCompanyId;
        $domain = app(ProjectRepository::class)->findVisible($project->id, $effectiveCompanyId, (int) auth()->id(), $canViewAll);

        if ($domain === null) {
            abort(404);
        }

        $this->projectId = $domain->id;
        $this->companyId = $domain->companyId;
        $this->selectCompanyId = $domain->companyId;
        $this->companyName = '';
        $this->name = $domain->name;
        $this->description = $domain->description ?? '';
        $this->status = $domain->status->value;
        $this->managerId = $domain->managerId;
        $this->startDate = $domain->startDate?->format('Y-m-d');
        $this->dueDate = $domain->dueDate?->format('Y-m-d');
        $this->memberIds = array_map(fn (array $member) => (int) $member['id'], app(ProjectRepository::class)->members($domain->id));
    }

    public function save(): void
    {
        $effectiveCompanyId = $this->companyId ?? $this->selectCompanyId;

        if ($effectiveCompanyId === null) {
            abort(403);
        }

        $this->companyId = $effectiveCompanyId;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'managerId' => ['required', 'integer', Rule::exists('users', 'id')->where('company_id', $this->companyId)],
            'startDate' => ['nullable', 'date'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'memberIds' => ['required', 'array'],
            'memberIds.*' => ['required', 'integer', Rule::exists('users', 'id')->where('company_id', $this->companyId)],
        ]);

        $manager = app(UserRepository::class)->find($validated['managerId']);

        if ($manager === null || ! $manager->isActive() || in_array($manager->primaryRole()?->value, [RoleName::Auditor->value, RoleName::ReadOnly->value], true)) {
            $this->addError('managerId', 'El responsable del proyecto debe ser un usuario operativo activo.');
            Flux::toast(variant: 'danger', text: 'El responsable del proyecto debe ser un usuario operativo activo.');

            return;
        }

        foreach ($validated['memberIds'] as $memberId) {
            $member = app(UserRepository::class)->find($memberId);

            if ($member === null || $member->companyId !== $this->companyId || ! $member->isActive()) {
                $this->addError('memberIds', 'Uno de los miembros no pertenece a la empresa activa.');
                Flux::toast(variant: 'danger', text: 'Uno de los miembros no pertenece a la empresa activa.');

                return;
            }
        }

        $input = new ProjectInput(
            name: $validated['name'],
            description: $this->clean($validated['description'] ?? null),
            status: ProjectStatus::from($validated['status']),
            managerId: (int) $validated['managerId'],
            startDate: $validated['startDate'] !== null ? new \DateTimeImmutable($validated['startDate']) : null,
            dueDate: $validated['dueDate'] !== null ? new \DateTimeImmutable($validated['dueDate']) : null,
            memberIds: array_values(array_unique(array_map('intval', $validated['memberIds']))),
        );

        try {
            if ($this->projectId === null) {
                $project = app(CreateProject::class)->execute(
                    companyId: $this->companyId,
                    input: $input,
                    actorId: (int) auth()->id(),
                );

                Flux::toast(variant: 'success', text: 'Proyecto creado correctamente.');
                $this->redirectRoute('projects.show', $project->id, navigate: true);

                return;
            }

            app(UpdateProject::class)->execute(
                companyId: $this->companyId,
                projectId: $this->projectId,
                input: $input,
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Proyecto actualizado correctamente.');
            $this->redirectRoute('projects.show', $this->projectId, navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('projects.livewire.form', [
            'isEditing' => $this->projectId !== null,
            'companyOptions' => $this->companyOptions,
        ]);
    }

    private function loadUserOptions(): void
    {
        $effectiveCompanyId = $this->companyId ?? $this->selectCompanyId;

        if ($effectiveCompanyId === null) {
            return;
        }

        $users = app(UserRepository::class)->forCompany($effectiveCompanyId);

        $this->userOptions = array_map(
            fn ($user) => ['id' => $user->id, 'name' => $user->name],
            array_values(array_filter($users, fn ($user) => $user->isActive())),
        );
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
