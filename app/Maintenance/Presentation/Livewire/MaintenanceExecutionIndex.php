<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Application\DTOs\MaintenanceExecutionFilters;
use App\Maintenance\Application\UseCases\SearchMaintenanceExecutions;
use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Ejecuciones de mantenimiento')]
class MaintenanceExecutionIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $plan = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<string, string> */
    public array $typeOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', MaintenanceExecutionModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->statusOptions = collect(MaintenanceExecutionStatus::cases())
            ->mapWithKeys(fn (MaintenanceExecutionStatus $status) => [$status->value => $status->label()])
            ->all();

        $this->typeOptions = collect(MaintenanceExecutionType::cases())
            ->mapWithKeys(fn (MaintenanceExecutionType $type) => [$type->value => $type->label()])
            ->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'type', 'plan'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'type', 'plan']);
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('maintenance.livewire.executions-index', [
                'executions' => new LengthAwarePaginator([], 0, 12),
                'counts' => ['scheduled' => 0, 'in_progress' => 0, 'completed_month' => 0, 'cancelled_month' => 0],
                'companyName' => '',
                'statusOptions' => $this->statusOptions,
                'typeOptions' => $this->typeOptions,
            ]);
        }

        $filters = new MaintenanceExecutionFilters(
            term: $this->search !== '' ? $this->search : null,
            status: $this->status !== '' ? MaintenanceExecutionStatus::tryFrom($this->status) : null,
            type: $this->type !== '' ? MaintenanceExecutionType::tryFrom($this->type) : null,
            planId: $this->plan !== '' ? (int) $this->plan : null,
        );

        $result = app(SearchMaintenanceExecutions::class)->execute(
            companyId: $this->companyId,
            filters: $filters,
            page: $this->getPage(),
            perPage: 12,
        );

        $executions = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('maintenance.executions.index')],
        );

        return view('maintenance.livewire.executions-index', [
            'executions' => $executions,
            'counts' => app(MaintenanceExecutionRepository::class)->counts($this->companyId),
            'companyName' => $this->companyName,
            'statusOptions' => $this->statusOptions,
            'typeOptions' => $this->typeOptions,
        ]);
    }
}
