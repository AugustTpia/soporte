<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Application\DTOs\MaintenancePlanFilters;
use App\Maintenance\Application\UseCases\SearchMaintenancePlans;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Mantenimientos')]
class MaintenancePlanIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $targetType = '';

    #[Url]
    public bool $overdue = false;

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<string, string> */
    public array $targetTypeOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', MaintenancePlanModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->statusOptions = collect(MaintenancePlanStatus::cases())
            ->mapWithKeys(fn (MaintenancePlanStatus $status) => [$status->value => $status->label()])
            ->all();

        $this->targetTypeOptions = collect(MaintenanceTargetType::cases())
            ->mapWithKeys(fn (MaintenanceTargetType $type) => [$type->value => $type->label()])
            ->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'targetType', 'overdue'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'targetType', 'overdue']);
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('maintenance.livewire.plans-index', [
                'plans' => new LengthAwarePaginator([], 0, 12),
                'counts' => ['active' => 0, 'paused' => 0, 'overdue' => 0, 'upcoming' => 0],
                'companyName' => '',
                'statusOptions' => $this->statusOptions,
                'targetTypeOptions' => $this->targetTypeOptions,
            ]);
        }

        $filters = new MaintenancePlanFilters(
            term: $this->search !== '' ? $this->search : null,
            targetType: $this->targetType !== '' ? MaintenanceTargetType::tryFrom($this->targetType) : null,
            status: $this->status !== '' ? MaintenancePlanStatus::tryFrom($this->status) : null,
            overdue: $this->overdue,
        );

        $result = app(SearchMaintenancePlans::class)->execute(
            companyId: $this->companyId,
            filters: $filters,
            page: $this->getPage(),
            perPage: 12,
        );

        $plans = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('maintenance.index')],
        );

        return view('maintenance.livewire.plans-index', [
            'plans' => $plans,
            'counts' => app(MaintenancePlanRepository::class)->counts($this->companyId),
            'companyName' => $this->companyName,
            'statusOptions' => $this->statusOptions,
            'targetTypeOptions' => $this->targetTypeOptions,
        ]);
    }
}
