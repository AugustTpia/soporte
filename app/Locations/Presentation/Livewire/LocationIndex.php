<?php

namespace App\Locations\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Locations\Application\UseCases\SearchLocations;
use App\Locations\Application\UseCases\ToggleLocationStatus;
use App\Locations\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Ubicaciones')]
class LocationIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?int $companyId = null;

    public string $companyName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', LocationModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $locationId): void
    {
        $this->authorize('update', LocationModel::query()->findOrFail($locationId));

        if ($this->companyId === null) {
            abort(403);
        }

        try {
            app(ToggleLocationStatus::class)->execute($this->companyId, $locationId);

            Flux::toast(variant: 'success', text: 'Estado de la ubicación actualizado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('locations.livewire.index', [
                'locations' => new LengthAwarePaginator([], 0, 10),
                'companyName' => '',
            ]);
        }

        $result = app(SearchLocations::class)->execute(
            $this->companyId,
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
            $this->status === '' ? null : $this->status === 'active',
        );

        $locations = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('locations.index')],
        );

        return view('locations.livewire.index', [
            'locations' => $locations,
            'companyName' => $this->companyName,
        ]);
    }
}
