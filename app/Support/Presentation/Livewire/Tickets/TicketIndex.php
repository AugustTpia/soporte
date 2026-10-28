<?php

namespace App\Support\Presentation\Livewire\Tickets;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Support\Application\DTOs\TicketFilters;
use App\Support\Application\UseCases\SearchTickets;
use App\Support\Domain\Enums\TicketCategory;
use App\Support\Domain\Enums\TicketPriority;
use App\Support\Domain\Enums\TicketStatus;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Tickets')]
class TicketIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $scope = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $category = '';

    public ?int $companyId = null;

    public string $companyName = '';

    /** @var array<string, string> */
    public array $scopeOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    /** @var array<string, string> */
    public array $priorityOptions = [];

    /** @var array<string, string> */
    public array $categoryOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', SupportTicketModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->scopeOptions = [
            '' => 'Todos los tickets',
            'mine' => 'Mis tickets activos',
            'unassigned' => 'Sin asignar',
            'unresolved' => 'Sin resolver',
        ];

        $this->statusOptions = collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status) => [$status->value => $status->label()])
            ->all();

        $this->priorityOptions = collect(TicketPriority::cases())
            ->mapWithKeys(fn (TicketPriority $priority) => [$priority->value => $priority->label()])
            ->all();

        $this->categoryOptions = collect(TicketCategory::cases())
            ->mapWithKeys(fn (TicketCategory $category) => [$category->value => $category->label()])
            ->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'scope', 'status', 'priority', 'category'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'scope', 'status', 'priority', 'category']);
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('tickets.livewire.index', [
                'tickets' => new LengthAwarePaginator([], 0, 12),
                'companyName' => '',
                'scopeOptions' => $this->scopeOptions,
                'statusOptions' => $this->statusOptions,
                'priorityOptions' => $this->priorityOptions,
                'categoryOptions' => $this->categoryOptions,
            ]);
        }

        $userId = (int) auth()->id();
        $status = $this->status !== '' ? TicketStatus::tryFrom($this->status) : null;

        $filters = new TicketFilters(
            term: $this->search !== '' ? $this->search : null,
            status: $status,
            priority: $this->priority !== '' ? TicketPriority::tryFrom($this->priority) : null,
            category: $this->category !== '' ? TicketCategory::tryFrom($this->category) : null,
            assignedTo: $this->scope === 'mine' ? $userId : null,
            unassigned: $this->scope === 'unassigned',
            unresolved: $status === null && in_array($this->scope, ['mine', 'unassigned', 'unresolved'], true),
        );

        $result = app(SearchTickets::class)->execute(
            companyId: $this->companyId,
            filters: $filters,
            page: $this->getPage(),
            perPage: 12,
        );

        $tickets = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('tickets.index')],
        );

        return view('tickets.livewire.index', [
            'tickets' => $tickets,
            'companyName' => $this->companyName,
            'scopeOptions' => $this->scopeOptions,
            'statusOptions' => $this->statusOptions,
            'priorityOptions' => $this->priorityOptions,
            'categoryOptions' => $this->categoryOptions,
        ]);
    }
}
