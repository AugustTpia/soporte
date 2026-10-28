<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Assets\Domain\Repositories\AssetRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Maintenance\Application\DTOs\MaintenanceExecutionInput;
use App\Maintenance\Application\DTOs\MaintenanceExecutionUpdateInput;
use App\Maintenance\Application\UseCases\ScheduleMaintenanceExecution;
use App\Maintenance\Application\UseCases\UpdateMaintenanceExecution;
use App\Maintenance\Domain\Enums\MaintenanceExecutionType;
use App\Maintenance\Domain\Enums\MaintenancePlanStatus;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionRepository;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Domain\Repositories\TicketRepository;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Ejecución de mantenimiento')]
class MaintenanceExecutionForm extends Component
{
    public ?int $executionId = null;

    public ?int $planId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public bool $isPlanBased = false;

    public string $planTitle = '';

    public string $planTargetLabel = '';

    public string $type = 'corrective';

    public string $targetType = 'asset';

    public string $assetId = '';

    public string $locationId = '';

    public string $supportTicketId = '';

    public string $scheduledAt = '';

    public string $technicianId = '';

    public string $findings = '';

    public string $actions = '';

    public string $recommendations = '';

    public string $partsUsed = '';

    public string $timeSpentMinutes = '';

    /** @var array<int, array{id: int, label: string}> */
    public array $assetOptions = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $locationOptions = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $technicianOptions = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $ticketOptions = [];

    /** @var array<string, string> */
    public array $targetTypeOptions = [];

    public function mount(?MaintenanceExecutionModel $execution = null, ?int $plan = null): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        $this->targetTypeOptions = collect(MaintenanceTargetType::cases())
            ->mapWithKeys(fn (MaintenanceTargetType $type) => [$type->value => $type->label()])
            ->all();

        if ($execution === null) {
            $this->authorize('create', MaintenanceExecutionModel::class);
            $this->scheduledAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');

            if ($plan !== null) {
                $planModel = app(MaintenancePlanRepository::class)->find($plan, $this->companyId);

                if ($planModel === null) {
                    abort(404);
                }

                if ($planModel->status !== MaintenancePlanStatus::Active) {
                    Flux::toast(variant: 'danger', text: 'El plan debe estar activo para programar una ejecución.');
                    $this->redirectRoute('maintenance.plans.show', $planModel->id, navigate: true);

                    return;
                }

                $this->planId = $planModel->id;
                $this->isPlanBased = true;
                $this->type = MaintenanceExecutionType::Preventive->value;
                $this->targetType = $planModel->targetType->value;
                $this->assetId = $planModel->assetId === null ? '' : (string) $planModel->assetId;
                $this->locationId = $planModel->locationId === null ? '' : (string) $planModel->locationId;
                $this->planTitle = $planModel->title;
                $this->planTargetLabel = $planModel->targetLabel;
            }

            $this->loadOptions();

            return;
        }

        $this->authorize('update', $execution);
        $domain = app(MaintenanceExecutionRepository::class)->find($execution->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        if (! $domain->status->isEditable()) {
            $this->redirectRoute('maintenance.executions.show', $domain->id, navigate: true);

            return;
        }

        $this->executionId = $domain->id;
        $this->planId = $domain->planId;
        $this->isPlanBased = $domain->planId !== null;
        $this->type = $domain->type->value;
        $this->targetType = $domain->targetType->value;
        $this->assetId = $domain->assetId === null ? '' : (string) $domain->assetId;
        $this->locationId = $domain->locationId === null ? '' : (string) $domain->locationId;
        $this->supportTicketId = $domain->supportTicketId === null ? '' : (string) $domain->supportTicketId;
        $this->scheduledAt = $domain->scheduledAt->format('Y-m-d\TH:i');
        $this->technicianId = (string) $domain->technicianId;
        $this->findings = $domain->findings ?? '';
        $this->actions = $domain->actions ?? '';
        $this->recommendations = $domain->recommendations ?? '';
        $this->partsUsed = $domain->partsUsed ?? '';
        $this->timeSpentMinutes = $domain->timeSpentMinutes === null ? '' : (string) $domain->timeSpentMinutes;
        $this->planTitle = $domain->planTitle ?? '';
        $this->planTargetLabel = $domain->targetLabel;

        $this->loadOptions();
    }

    public function updatedTargetType(): void
    {
        $this->assetId = '';
        $this->locationId = '';
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'scheduledAt' => ['required', 'date_format:Y-m-d\TH:i'],
            'technicianId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'supportTicketId' => [
                'nullable',
                'integer',
                Rule::exists('support_tickets', 'id')->where('company_id', $this->companyId),
            ],
            'findings' => ['nullable', 'string', 'max:5000'],
            'actions' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'partsUsed' => ['nullable', 'string', 'max:2000'],
            'timeSpentMinutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'targetType' => ['required', Rule::enum(MaintenanceTargetType::class)],
            'assetId' => [
                'nullable',
                'integer',
                Rule::requiredIf(! $this->isPlanBased && $this->targetType === MaintenanceTargetType::Asset->value),
                Rule::exists('assets', 'id')->where('company_id', $this->companyId),
            ],
            'locationId' => [
                'nullable',
                'integer',
                Rule::requiredIf(! $this->isPlanBased && $this->targetType === MaintenanceTargetType::Location->value),
                Rule::exists('locations', 'id')->where('company_id', $this->companyId),
            ],
        ]);

        try {
            if ($this->executionId !== null) {
                app(UpdateMaintenanceExecution::class)->execute(
                    companyId: $this->companyId,
                    executionId: $this->executionId,
                    input: new MaintenanceExecutionUpdateInput(
                        scheduledAt: $this->dateTime($validated['scheduledAt']),
                        technicianId: (int) $validated['technicianId'],
                        findings: $this->clean($validated['findings'] ?? null),
                        actions: $this->clean($validated['actions'] ?? null),
                        recommendations: $this->clean($validated['recommendations'] ?? null),
                        partsUsed: $this->clean($validated['partsUsed'] ?? null),
                        timeSpentMinutes: $this->integerOrNull($validated['timeSpentMinutes'] ?? null),
                    ),
                );

                Flux::toast(variant: 'success', text: 'Ejecución actualizada correctamente.');
                $this->redirectRoute('maintenance.executions.show', $this->executionId, navigate: true);

                return;
            }

            $execution = app(ScheduleMaintenanceExecution::class)->execute(
                companyId: $this->companyId,
                input: new MaintenanceExecutionInput(
                    planId: $this->planId,
                    targetType: MaintenanceTargetType::from($validated['targetType']),
                    assetId: $this->integerOrNull($validated['assetId'] ?? null),
                    locationId: $this->integerOrNull($validated['locationId'] ?? null),
                    supportTicketId: $this->integerOrNull($validated['supportTicketId'] ?? null),
                    type: MaintenanceExecutionType::from($this->type),
                    scheduledAt: $this->dateTime($validated['scheduledAt']),
                    technicianId: (int) $validated['technicianId'],
                ),
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Mantenimiento programado correctamente.');
            $this->redirectRoute('maintenance.executions.show', $execution->id, navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $execution = $this->executionId === null || $this->companyId === null
            ? null
            : app(MaintenanceExecutionRepository::class)->find($this->executionId, $this->companyId);

        return view('maintenance.livewire.executions-form', [
            'isEditing' => $this->executionId !== null,
            'execution' => $execution,
        ]);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->assetOptions = collect(app(AssetRepository::class)->forCompany($this->companyId))
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'label' => $asset->internalCode.($asset->hostname !== null ? ' · '.$asset->hostname : ''),
            ])
            ->all();

        $this->locationOptions = collect(app(LocationRepository::class)->forCompany($this->companyId))
            ->map(fn ($location) => [
                'id' => $location->id,
                'label' => $location->name,
            ])
            ->all();

        $this->technicianOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->filter(fn ($user) => $user->isActive())
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();

        $this->ticketOptions = collect(app(TicketRepository::class)->forCompany($this->companyId))
            ->map(fn ($ticket) => [
                'id' => $ticket->id,
                'label' => ($ticket->folio ?? 'Ticket').' · '.$ticket->subject,
            ])
            ->all();
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integerOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function dateTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);

        if ($date === false) {
            throw DomainException::because('La fecha programada no es válida.');
        }

        return $date;
    }
}
