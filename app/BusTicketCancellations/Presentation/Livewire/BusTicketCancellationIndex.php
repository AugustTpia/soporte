<?php

namespace App\BusTicketCancellations\Presentation\Livewire;

use App\BusTicketCancellations\Application\DTOs\BusTicketCancellationFilters;
use App\BusTicketCancellations\Application\UseCases\SearchBusTicketCancellations;
use App\BusTicketCancellations\Application\UseCases\SummarizeBusTicketCancellations;
use App\BusTicketCancellations\Domain\Enums\BusTicketCancellationResult;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Cancelación de boletos')]
class BusTicketCancellationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    #[Url]
    public string $result = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public ?int $companyId = null;

    public string $companyName = '';

    public string $timezone = 'America/Mexico_City';

    /** @var array<string, string> */
    public array $resultOptions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', BusTicketCancellationModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);

        if ($company === null) {
            abort(404);
        }

        $this->companyName = $company->name;
        $this->timezone = $company->timezone;

        $this->resultOptions = collect(BusTicketCancellationResult::cases())
            ->mapWithKeys(fn (BusTicketCancellationResult $result) => [$result->value => $result->label()])
            ->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'result', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'result', 'from', 'to']);
        $this->resetPage();
    }

    public function setCurrentFortnight(): void
    {
        $now = CarbonImmutable::now($this->timezone);

        if ($now->day <= 15) {
            $start = $now->startOfMonth();
            $end = $now->startOfMonth()->addDays(14);
        } else {
            $start = $now->startOfMonth()->addDays(15);
            $end = $now->endOfMonth();
        }

        $this->from = $start->format('Y-m-d');
        $this->to = $end->format('Y-m-d');
        $this->resetPage();
    }

    public function setCurrentMonth(): void
    {
        $now = CarbonImmutable::now($this->timezone);

        $this->from = $now->startOfMonth()->format('Y-m-d');
        $this->to = $now->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('bus-ticket-cancellations.livewire.index', [
                'cancellations' => new LengthAwarePaginator([], 0, 20),
                'summary' => $this->emptySummary(),
                'companyName' => '',
                'resultOptions' => $this->resultOptions,
                'canManage' => false,
                'exportUrl' => $this->exportUrl(),
            ]);
        }

        $filters = new BusTicketCancellationFilters(
            term: $this->search !== '' ? $this->search : null,
            result: $this->result !== '' ? BusTicketCancellationResult::tryFrom($this->result) : null,
            from: $this->date($this->from),
            to: $this->date($this->to),
        );

        $result = app(SearchBusTicketCancellations::class)->execute(
            companyId: $this->companyId,
            filters: $filters,
            page: $this->getPage(),
            perPage: 20,
        );

        $cancellations = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('bus-ticket-cancellations.index')],
        );

        return view('bus-ticket-cancellations.livewire.index', [
            'cancellations' => $cancellations,
            'summary' => app(SummarizeBusTicketCancellations::class)->execute($this->companyId, $filters),
            'companyName' => $this->companyName,
            'resultOptions' => $this->resultOptions,
            'canManage' => auth()->user()->can('bus_ticket_cancellations.manage'),
            'exportUrl' => $this->exportUrl(),
        ]);
    }

    private function exportUrl(): string
    {
        return route('bus-ticket-cancellations.export', array_filter([
            'search' => $this->search,
            'result' => $this->result,
            'from' => $this->from,
            'to' => $this->to,
        ], fn (string $value): bool => $value !== ''));
    }

    private function date(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }

    /**
     * @return array{total: int, cancelled: int, not_cancelled: int, pending: int, total_amount_cents: int}
     */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'cancelled' => 0,
            'not_cancelled' => 0,
            'pending' => 0,
            'total_amount_cents' => 0,
        ];
    }
}
