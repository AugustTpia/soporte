<?php

namespace App\Reports\Presentation\Livewire;

use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Repositories\UserRepository;
use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Reports\Application\DTOs\CancellationReportFilters;
use App\Reports\Application\DTOs\MaintenanceReportFilters;
use App\Reports\Application\DTOs\ReportPeriod;
use App\Reports\Application\DTOs\TicketReportFilters;
use App\Reports\Application\UseCases\BuildCancellationReport;
use App\Reports\Application\UseCases\BuildMaintenanceReport;
use App\Reports\Application\UseCases\BuildTicketReport;
use App\Reports\Domain\Enums\ReportType;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Reportes')]
class ReportIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $report = 'tickets';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $technician = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $plan = '';

    #[Url]
    public string $result = '';

    #[Url]
    public string $operator = '';

    public ?int $companyId = null;

    public string $companyName = '';

    public bool $cancellationsEnabled = false;

    /** @var array<string, string> */
    public array $availableReports = [];

    /** @var array<string, string> */
    public array $ticketStatusOptions = [];

    /** @var array<string, string> */
    public array $maintenanceStatusOptions = [];

    /** @var array<string, string> */
    public array $priorityOptions = [];

    /** @var array<string, string> */
    public array $categoryOptions = [];

    /** @var array<string, string> */
    public array $typeOptions = [];

    /** @var array<string, string> */
    public array $resultOptions = [];

    /** @var array<int, string> */
    public array $technicianOptions = [];

    /** @var array<int, string> */
    public array $planOptions = [];

    public function mount(): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = (string) $company?->name;
        $this->cancellationsEnabled = $company?->hasFeature(CompanyFeature::BusTicketCancellations) ?? false;

        $this->availableReports = $this->resolveAvailableReports();

        if ($this->availableReports === []) {
            abort(403);
        }

        if (! array_key_exists($this->report, $this->availableReports)) {
            $this->report = (string) array_key_first($this->availableReports);
        }

        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo = now()->endOfMonth()->toDateString();
        $this->from = $this->from !== '' ? $this->from : $defaultFrom;
        $this->to = $this->to !== '' ? $this->to : $defaultTo;

        $this->ticketStatusOptions = collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status) => [$status->value => $status->label()])
            ->all();
        $this->maintenanceStatusOptions = collect(MaintenanceExecutionStatus::cases())
            ->mapWithKeys(fn (MaintenanceExecutionStatus $status) => [$status->value => $status->label()])
            ->all();
        $this->priorityOptions = collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $priority) => [$priority->value => $priority->label()])
            ->all();
        $this->categoryOptions = collect(TicketCategory::cases())
            ->mapWithKeys(fn (TicketCategory $category) => [$category->value => $category->label()])
            ->all();
        $this->typeOptions = collect(MaintenanceExecutionType::cases())
            ->mapWithKeys(fn (MaintenanceExecutionType $type) => [$type->value => $type->label()])
            ->all();
        $this->resultOptions = collect(BusTicketCancellationResult::cases())
            ->mapWithKeys(fn (BusTicketCancellationResult $result) => [$result->value => $result->label()])
            ->all();

        $this->technicianOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->filter(fn (User $user) => $user->isActive() && $user->id !== null)
            ->mapWithKeys(fn (User $user) => [(int) $user->id => $user->name])
            ->all();

        $this->planOptions = collect(app(MaintenancePlanRepository::class)->forCompany($this->companyId))
            ->filter(fn ($plan) => $plan->id !== null)
            ->mapWithKeys(fn ($plan) => [(int) $plan->id => $plan->title])
            ->all();
    }

    public function updated(string $property): void
    {
        if ($property === 'report' && ! array_key_exists($this->report, $this->resolveAvailableReports())) {
            abort(403);
        }

        if ($property === 'from' || $property === 'to') {
            $this->validatePeriodFields();
        }

        if (in_array($property, ['search', 'status', 'priority', 'category', 'technician', 'type', 'plan', 'result', 'operator', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function switchReport(string $report): void
    {
        $availableReports = $this->resolveAvailableReports();

        if (! array_key_exists($report, $availableReports)) {
            abort(403);
        }

        $this->availableReports = $availableReports;
        $this->report = $report;
        $this->reset(['search', 'status', 'priority', 'category', 'technician', 'type', 'plan', 'result', 'operator']);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'priority', 'category', 'technician', 'type', 'plan', 'result', 'operator']);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
        $this->resetErrorBag();
        $this->resetPage();
    }

    public function render(): View
    {
        $viewData = [
            'companyName' => $this->companyName,
            'availableReports' => $this->availableReports,
            'reportDescription' => ReportType::from($this->report)->description(),
            'ticketStatusOptions' => $this->ticketStatusOptions,
            'maintenanceStatusOptions' => $this->maintenanceStatusOptions,
            'priorityOptions' => $this->priorityOptions,
            'categoryOptions' => $this->categoryOptions,
            'typeOptions' => $this->typeOptions,
            'resultOptions' => $this->resultOptions,
            'technicianOptions' => $this->technicianOptions,
            'planOptions' => $this->planOptions,
        ];

        if ($this->companyId === null) {
            return view('reports.livewire.index', $viewData + $this->emptyReportData());
        }

        try {
            $period = ReportPeriod::fromStrings($this->from, $this->to);
        } catch (DomainException $exception) {
            return view('reports.livewire.index', $viewData + $this->emptyReportData($exception->getMessage()));
        }

        $type = ReportType::from($this->report);

        $result = match ($type) {
            ReportType::Tickets => app(BuildTicketReport::class)->page(
                $this->companyId,
                $this->ticketFilters($period),
                $this->getPage(),
                20,
            ),
            ReportType::Maintenance => app(BuildMaintenanceReport::class)->page(
                $this->companyId,
                $this->maintenanceFilters($period),
                $this->getPage(),
                20,
            ),
            ReportType::Cancellations => app(BuildCancellationReport::class)->page(
                $this->companyId,
                $this->cancellationFilters($period),
                $this->getPage(),
                20,
            ),
        };

        $metrics = match ($type) {
            ReportType::Tickets => app(BuildTicketReport::class)->metrics($this->companyId, $this->ticketFilters($period)),
            ReportType::Maintenance => app(BuildMaintenanceReport::class)->metrics($this->companyId, $this->maintenanceFilters($period)),
            ReportType::Cancellations => app(BuildCancellationReport::class)->metrics($this->companyId, $this->cancellationFilters($period)),
        };

        $paginator = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            [
                'path' => route('reports.index'),
                'query' => $this->queryParams(),
            ],
        );

        return view('reports.livewire.index', $viewData + [
            'periodLabel' => $period->label(),
            'periodError' => null,
            'metrics' => $metrics,
            'items' => $result->items,
            'paginator' => $paginator,
            'statusOptions' => match ($type) {
                ReportType::Tickets => $this->ticketStatusOptions,
                ReportType::Maintenance => $this->maintenanceStatusOptions,
                ReportType::Cancellations => [],
            },
            'exportUrls' => $this->exportUrls(),
            'hasFilters' => $this->hasFilters(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function resolveAvailableReports(): array
    {
        $reports = [];

        if (Gate::allows('viewAny', SupportTicketModel::class)) {
            $reports[ReportType::Tickets->value] = ReportType::Tickets->label();
        }

        if (Gate::allows('viewAny', MaintenanceExecutionModel::class)) {
            $reports[ReportType::Maintenance->value] = ReportType::Maintenance->label();
        }

        if ($this->cancellationsEnabled && Gate::allows('viewAny', BusTicketCancellationModel::class)) {
            $reports[ReportType::Cancellations->value] = ReportType::Cancellations->label();
        }

        return $reports;
    }

    private function ticketFilters(ReportPeriod $period): TicketReportFilters
    {
        return new TicketReportFilters(
            period: $period,
            term: $this->searchTerm(),
            status: $this->status !== '' ? TicketStatus::tryFrom($this->status) : null,
            priority: $this->priority !== '' ? TicketPriority::tryFrom($this->priority) : null,
            category: $this->category !== '' ? TicketCategory::tryFrom($this->category) : null,
            technicianId: $this->positiveInt($this->technician),
        );
    }

    private function maintenanceFilters(ReportPeriod $period): MaintenanceReportFilters
    {
        return new MaintenanceReportFilters(
            period: $period,
            term: $this->searchTerm(),
            status: $this->status !== '' ? MaintenanceExecutionStatus::tryFrom($this->status) : null,
            type: $this->type !== '' ? MaintenanceExecutionType::tryFrom($this->type) : null,
            technicianId: $this->positiveInt($this->technician),
            planId: $this->positiveInt($this->plan),
        );
    }

    private function cancellationFilters(ReportPeriod $period): CancellationReportFilters
    {
        return new CancellationReportFilters(
            period: $period,
            term: $this->searchTerm(),
            result: $this->result !== '' ? BusTicketCancellationResult::tryFrom($this->result) : null,
            operatorId: $this->positiveInt($this->operator),
        );
    }

    private function validatePeriodFields(): void
    {
        $this->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($this->from > $this->to) {
            $this->addError('to', 'La fecha final debe ser igual o posterior a la fecha inicial.');
        }
    }

    private function searchTerm(): ?string
    {
        $term = trim(mb_substr($this->search, 0, 200));

        return $term === '' ? null : $term;
    }

    private function positiveInt(string $value): ?int
    {
        if (! ctype_digit($value)) {
            return null;
        }

        $number = (int) $value;

        return $number > 0 ? $number : null;
    }

    /**
     * @return array<string, string>
     */
    private function queryParams(): array
    {
        $params = [
            'report' => $this->report,
            'from' => $this->from,
            'to' => $this->to,
        ];

        foreach ([
            'search' => $this->search,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'technician' => $this->technician,
            'type' => $this->type,
            'plan' => $this->plan,
            'result' => $this->result,
            'operator' => $this->operator,
        ] as $key => $value) {
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * @return array{csv: string, pdf: string}
     */
    private function exportUrls(): array
    {
        $query = $this->queryParams();
        unset($query['report']);

        return [
            'csv' => route('reports.export', ['report' => $this->report, 'format' => 'csv'] + $query),
            'pdf' => route('reports.export', ['report' => $this->report, 'format' => 'pdf'] + $query),
        ];
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->priority !== ''
            || $this->category !== ''
            || $this->technician !== ''
            || $this->type !== ''
            || $this->plan !== ''
            || $this->result !== ''
            || $this->operator !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReportData(?string $error = null): array
    {
        return [
            'periodLabel' => '',
            'periodError' => $error,
            'metrics' => [],
            'items' => [],
            'paginator' => new LengthAwarePaginator([], 0, 20),
            'statusOptions' => [],
            'exportUrls' => [],
            'hasFilters' => false,
        ];
    }
}
