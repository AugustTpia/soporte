<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Application\UseCases\ChangeMaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Plan de mantenimiento')]
class MaintenancePlanShow extends Component
{
    public int $planId;

    public ?int $companyId = null;

    public string $companyName = '';

    public function mount(int $plan): void
    {
        $this->authorize('viewAny', MaintenancePlanModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $model = $this->model($plan);
        $this->authorize('view', $model);

        $this->planId = $model->id;
        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';
    }

    public function toggleStatus(): void
    {
        $model = $this->model($this->planId);
        $this->authorize('update', $model);

        $target = $model->status === MaintenancePlanStatus::Active->value
            ? MaintenancePlanStatus::Paused
            : MaintenancePlanStatus::Active;

        try {
            app(ChangeMaintenancePlanStatus::class)->execute(
                companyId: (int) $this->companyId,
                planId: $this->planId,
                status: $target,
            );

            Flux::toast(variant: 'success', text: "Plan {$target->label()} correctamente.");
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $model = $this->model($this->planId);
        $plan = app(MaintenancePlanRepository::class)->find($this->planId, (int) $this->companyId);

        abort_if($plan === null, 404);

        return view('maintenance.livewire.plans-show', [
            'plan' => $plan,
            'executions' => app(MaintenanceExecutionRepository::class)->forPlan($this->planId, (int) $this->companyId),
            'canManage' => Gate::allows('update', $model),
            'companyName' => $this->companyName,
        ]);
    }

    private function model(int $id): MaintenancePlanModel
    {
        return MaintenancePlanModel::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($id);
    }
}
