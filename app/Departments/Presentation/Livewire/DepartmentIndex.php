<?php

namespace App\Departments\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Departments\Application\UseCases\SearchDepartments;
use App\Departments\Application\UseCases\ToggleDepartmentStatus;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
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
#[Title('Departamentos')]
class DepartmentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public ?int $companyId = null;

    public string $companyName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', DepartmentModel::class);

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

    public function toggleStatus(int $departmentId): void
    {
        $this->authorize('update', DepartmentModel::query()->findOrFail($departmentId));

        if ($this->companyId === null) {
            abort(403);
        }

        try {
            app(ToggleDepartmentStatus::class)->execute($this->companyId, $departmentId);

            Flux::toast(variant: 'success', text: 'Estado del departamento actualizado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        if ($this->companyId === null) {
            return view('departments.livewire.index', [
                'departments' => new LengthAwarePaginator([], 0, 15),
                'companyName' => '',
            ]);
        }

        $result = app(SearchDepartments::class)->execute(
            $this->companyId,
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
            $this->status === '' ? null : $this->status === 'active',
        );

        $departments = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('departments.index')],
        );

        return view('departments.livewire.index', [
            'departments' => $departments,
            'companyName' => $this->companyName,
        ]);
    }
}
