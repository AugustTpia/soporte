<?php

namespace App\Projects\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Enums\RoleName;
use App\Projects\Application\DTOs\ProjectFilters;
use App\Projects\Application\UseCases\SearchProjects;
use App\Projects\Domain\Enums\ProjectStatus;
use App\Projects\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Proyectos')]
class ProjectIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $status = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', ProjectModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->statusOptions = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage();
    }

    public function render(): View
    {
        $filters = new ProjectFilters(
            term: $this->search !== '' ? $this->search : null,
            status: $this->status !== '' ? $this->status : null,
        );

        $result = app(SearchProjects::class)->execute(
            companyId: $this->companyId,
            userId: (int) auth()->id(),
            filters: $filters,
            page: $this->getPage(),
            perPage: 12,
        );

        $projects = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('projects.index')],
        );

        $canManageAll = auth()->user()->isSuperAdmin() || auth()->user()->hasRole(RoleName::CompanyAdmin->value);

        return view('projects.livewire.index', [
            'projects' => $projects,
            'companyName' => $this->companyName,
            'statusOptions' => $this->statusOptions,
            'canManageAll' => $canManageAll,
        ]);
    }
}
