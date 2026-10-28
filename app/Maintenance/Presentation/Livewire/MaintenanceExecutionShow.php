<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Application\UseCases\CancelMaintenanceExecution;
use App\Maintenance\Application\UseCases\CompleteMaintenanceExecution;
use App\Maintenance\Application\UseCases\StartMaintenanceExecution;
use App\Maintenance\Application\UseCases\UpdateMaintenanceExecutionChecklist;
use App\Maintenance\Domain\Entities\MaintenanceExecution;
use App\Maintenance\Domain\Entities\MaintenanceExecutionChecklistItem;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionAttachmentRepository;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Mantenimiento')]
class MaintenanceExecutionShow extends Component
{
    public int $executionId;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $cancellationReason = '';

    public bool $showCancel = false;

    /** @var array<int, string> */
    public array $checklistNotes = [];

    public function mount(int $execution): void
    {
        $this->authorize('viewAny', MaintenanceExecutionModel::class);

        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $model = $this->model($execution);
        $this->authorize('view', $model);

        $this->executionId = $model->id;
        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $domain = app(MaintenanceExecutionRepository::class)->find($this->executionId, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        foreach ($domain->checklistItems as $item) {
            if ($item->id !== null) {
                $this->checklistNotes[$item->id] = $item->notes ?? '';
            }
        }
    }

    public function start(): void
    {
        $this->authorize('update', $this->model($this->executionId));

        try {
            app(StartMaintenanceExecution::class)->execute(
                companyId: (int) $this->companyId,
                executionId: $this->executionId,
            );

            Flux::toast(variant: 'success', text: 'Mantenimiento iniciado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function complete(): void
    {
        $this->authorize('update', $this->model($this->executionId));

        try {
            app(CompleteMaintenanceExecution::class)->execute(
                companyId: (int) $this->companyId,
                executionId: $this->executionId,
            );

            Flux::toast(variant: 'success', text: 'Mantenimiento completado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->authorize('update', $this->model($this->executionId));

        $validated = $this->validate([
            'cancellationReason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            app(CancelMaintenanceExecution::class)->execute(
                companyId: (int) $this->companyId,
                executionId: $this->executionId,
                reason: $validated['cancellationReason'],
            );

            $this->reset('cancellationReason', 'showCancel');
            Flux::toast(variant: 'success', text: 'Mantenimiento cancelado.');
        } catch (DomainException $exception) {
            $this->addError('cancellationReason', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function toggleChecklist(int $itemId): void
    {
        $this->authorize('update', $this->model($this->executionId));

        $execution = $this->domainExecution();
        $item = $this->findItem($execution->checklistItems, $itemId);

        if ($item === null) {
            $this->addError('checklist', 'El punto del checklist no existe.');

            return;
        }

        $notes = $this->checklistNotes[$itemId] ?? $item->notes;

        try {
            app(UpdateMaintenanceExecutionChecklist::class)->execute(
                companyId: (int) $this->companyId,
                executionId: $this->executionId,
                states: [[
                    'id' => $itemId,
                    'is_completed' => ! $item->isCompleted,
                    'notes' => $notes,
                ]],
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Checklist actualizado.');
        } catch (DomainException $exception) {
            $this->addError('checklist', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function saveNotes(int $itemId): void
    {
        $this->authorize('update', $this->model($this->executionId));

        $this->validate([
            "checklistNotes.{$itemId}" => ['nullable', 'string', 'max:1000'],
        ]);

        $execution = $this->domainExecution();
        $item = $this->findItem($execution->checklistItems, $itemId);

        if ($item === null) {
            $this->addError('checklist', 'El punto del checklist no existe.');

            return;
        }

        try {
            app(UpdateMaintenanceExecutionChecklist::class)->execute(
                companyId: (int) $this->companyId,
                executionId: $this->executionId,
                states: [[
                    'id' => $itemId,
                    'is_completed' => $item->isCompleted,
                    'notes' => $this->checklistNotes[$itemId] ?? null,
                ]],
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Nota del checklist guardada.');
        } catch (DomainException $exception) {
            $this->addError("checklistNotes.{$itemId}", $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $model = $this->model($this->executionId);
        $execution = app(MaintenanceExecutionRepository::class)->find($this->executionId, (int) $this->companyId);

        abort_if($execution === null, 404);

        $canManage = Gate::allows('update', $model);

        return view('maintenance.livewire.executions-show', [
            'execution' => $execution,
            'attachments' => app(MaintenanceExecutionAttachmentRepository::class)
                ->forExecution($this->executionId, (int) $this->companyId),
            'canManage' => $canManage,
            'isEditable' => $canManage && $execution->status->isEditable(),
            'companyName' => $this->companyName,
        ]);
    }

    private function domainExecution(): MaintenanceExecution
    {
        return app(MaintenanceExecutionRepository::class)->find($this->executionId, (int) $this->companyId)
            ?? abort(404);
    }

    /**
     * @param  array<int, MaintenanceExecutionChecklistItem>  $items
     */
    private function findItem(array $items, int $itemId): ?MaintenanceExecutionChecklistItem
    {
        foreach ($items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        return null;
    }

    private function model(int $id): MaintenanceExecutionModel
    {
        return MaintenanceExecutionModel::query()
            ->where('company_id', $this->companyId)
            ->findOrFail($id);
    }
}
