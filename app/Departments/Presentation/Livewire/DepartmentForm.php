<?php

namespace App\Departments\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Departments\Application\DTOs\DepartmentInput;
use App\Departments\Application\UseCases\CreateDepartment;
use App\Departments\Application\UseCases\UpdateDepartment;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Departments\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Departamento')]
class DepartmentForm extends Component
{
    public ?int $departmentId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $name = '';

    public string $description = '';

    public bool $isActive = true;

    public function mount(?DepartmentModel $department = null): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $actorCompany = auth()->user()->company;

        $this->companyName = app(TenantContext::class)->isSuperAdmin()
            ? ($company !== null ? $company->name : '')
            : ($actorCompany !== null ? $actorCompany->name : '');

        if ($department === null) {
            $this->authorize('create', DepartmentModel::class);

            return;
        }

        $this->authorize('update', $department);

        $domain = app(DepartmentRepository::class)->find($department->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->departmentId = $domain->id;
        $this->name = $domain->name;
        $this->description = $domain->description ?? '';
        $this->isActive = $domain->isActive;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['boolean'],
        ]);

        if ($this->companyId === null) {
            abort(403);
        }

        $input = new DepartmentInput(
            name: $validated['name'],
            description: $this->clean($validated['description'] ?? null),
            isActive: (bool) $validated['isActive'],
        );

        try {
            if ($this->departmentId === null) {
                app(CreateDepartment::class)->execute($this->companyId, $input);
                Flux::toast(variant: 'success', text: 'Departamento creado correctamente.');
            } else {
                app(UpdateDepartment::class)->execute($this->companyId, $this->departmentId, $input);
                Flux::toast(variant: 'success', text: 'Departamento actualizado correctamente.');
            }

            $this->redirectRoute('departments.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('departments.livewire.form', [
            'isEditing' => $this->departmentId !== null,
        ]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
