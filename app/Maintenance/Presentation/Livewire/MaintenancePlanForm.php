<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Assets\Domain\Repositories\AssetRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Locations\Domain\Repositories\LocationRepository;
use App\Maintenance\Application\DTOs\MaintenanceChecklistItemInput;
use App\Maintenance\Application\DTOs\MaintenancePlanInput;
use App\Maintenance\Application\UseCases\CreateMaintenancePlan;
use App\Maintenance\Application\UseCases\UpdateMaintenancePlan;
use App\Maintenance\Domain\Enums\MaintenanceIntervalUnit;
use App\Maintenance\Domain\Enums\MaintenanceTargetType;
use App\Maintenance\Domain\Repositories\MaintenancePlanRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Plan de mantenimiento')]
class MaintenancePlanForm extends Component
{
    public ?int $planId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $targetType = 'asset';

    public string $assetId = '';

    public string $locationId = '';

    public string $title = '';

    public string $description = '';

    public int|string $intervalValue = 1;

    public string $intervalUnit = 'months';

    public string $nextDueAt = '';

    public string $assignedUserId = '';

    /** @var array<int, array{label: string, is_required: bool}> */
    public array $checklist = [['label' => '', 'is_required' => true]];

    /** @var array<int, array{id: int, label: string}> */
    public array $assetOptions = [];

    /** @var array<int, array{id: int, label: string}> */
    public array $locationOptions = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $userOptions = [];

    /** @var array<string, string> */
    public array $targetTypeOptions = [];

    /** @var array<string, string> */
    public array $intervalUnitOptions = [];

    public function mount(?MaintenancePlanModel $plan = null): void
    {
        $this->companyId = app(TenantContext::class)->companyId();

        if ($this->companyId === null) {
            $this->redirectRoute('companies.index', navigate: true);

            return;
        }

        $company = app(CompanyRepository::class)->find($this->companyId);
        $this->companyName = $company !== null ? $company->name : '';

        if ($plan === null) {
            $this->authorize('create', MaintenancePlanModel::class);
            $this->nextDueAt = now()->addMonth()->startOfDay()->format('Y-m-d\TH:i');
            $this->loadOptions();
            $this->loadEnums();

            return;
        }

        $this->authorize('update', $plan);

        $domain = app(MaintenancePlanRepository::class)->find($plan->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->planId = $domain->id;
        $this->targetType = $domain->targetType->value;
        $this->assetId = $domain->assetId === null ? '' : (string) $domain->assetId;
        $this->locationId = $domain->locationId === null ? '' : (string) $domain->locationId;
        $this->title = $domain->title;
        $this->description = $domain->description ?? '';
        $this->intervalValue = $domain->intervalValue;
        $this->intervalUnit = $domain->intervalUnit->value;
        $this->nextDueAt = $domain->nextDueAt->format('Y-m-d\TH:i');
        $this->assignedUserId = (string) $domain->assignedUserId;
        $this->checklist = array_map(
            fn ($item) => ['label' => $item->label, 'is_required' => $item->isRequired],
            $domain->checklistItems,
        );

        $this->loadOptions();
        $this->loadEnums();
    }

    public function updatedTargetType(): void
    {
        $this->assetId = '';
        $this->locationId = '';
    }

    public function addChecklistItem(): void
    {
        $this->checklist[] = ['label' => '', 'is_required' => true];
    }

    public function removeChecklistItem(int $index): void
    {
        unset($this->checklist[$index]);
        $this->checklist = array_values($this->checklist);

        if ($this->checklist === []) {
            $this->checklist = [['label' => '', 'is_required' => true]];
        }
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'targetType' => ['required', Rule::enum(MaintenanceTargetType::class)],
            'assetId' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->targetType === MaintenanceTargetType::Asset->value),
                Rule::exists('assets', 'id')->where('company_id', $this->companyId),
            ],
            'locationId' => [
                'nullable',
                'integer',
                Rule::requiredIf($this->targetType === MaintenanceTargetType::Location->value),
                Rule::exists('locations', 'id')->where('company_id', $this->companyId),
            ],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'intervalValue' => ['required', 'integer', 'min:1', 'max:365'],
            'intervalUnit' => ['required', Rule::enum(MaintenanceIntervalUnit::class)],
            'nextDueAt' => ['required', 'date_format:Y-m-d\TH:i'],
            'assignedUserId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'checklist' => ['required', 'array', 'min:1', 'max:50'],
            'checklist.*.label' => ['required', 'string', 'max:200'],
            'checklist.*.is_required' => ['boolean'],
        ]);

        $input = new MaintenancePlanInput(
            targetType: MaintenanceTargetType::from($validated['targetType']),
            assetId: $this->integerOrNull($validated['assetId'] ?? null),
            locationId: $this->integerOrNull($validated['locationId'] ?? null),
            title: $validated['title'],
            description: $this->clean($validated['description'] ?? null),
            intervalValue: (int) $validated['intervalValue'],
            intervalUnit: MaintenanceIntervalUnit::from($validated['intervalUnit']),
            nextDueAt: $this->dateTime($validated['nextDueAt']),
            assignedUserId: (int) $validated['assignedUserId'],
            checklistItems: array_map(
                fn (array $item) => new MaintenanceChecklistItemInput(
                    label: $item['label'],
                    isRequired: (bool) $item['is_required'],
                ),
                array_values($validated['checklist']),
            ),
        );

        try {
            if ($this->planId === null) {
                $plan = app(CreateMaintenancePlan::class)->execute(
                    companyId: $this->companyId,
                    input: $input,
                    actorId: (int) auth()->id(),
                );
                Flux::toast(variant: 'success', text: 'Plan de mantenimiento creado correctamente.');
                $this->redirectRoute('maintenance.plans.show', $plan->id, navigate: true);

                return;
            }

            app(UpdateMaintenancePlan::class)->execute(
                companyId: $this->companyId,
                planId: $this->planId,
                input: $input,
            );

            Flux::toast(variant: 'success', text: 'Plan de mantenimiento actualizado correctamente.');
            $this->redirectRoute('maintenance.plans.show', $this->planId, navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('maintenance.livewire.plans-form', [
            'isEditing' => $this->planId !== null,
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

        $this->userOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->filter(fn ($user) => $user->isActive())
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    private function loadEnums(): void
    {
        $this->targetTypeOptions = collect(MaintenanceTargetType::cases())
            ->mapWithKeys(fn (MaintenanceTargetType $type) => [$type->value => $type->label()])
            ->all();

        $this->intervalUnitOptions = collect(MaintenanceIntervalUnit::cases())
            ->mapWithKeys(fn (MaintenanceIntervalUnit $unit) => [$unit->value => $unit->label()])
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
            throw DomainException::because('La próxima fecha no es válida.');
        }

        return $date;
    }
}
