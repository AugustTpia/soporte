<?php

namespace App\Assets\Presentation\Livewire;

use App\Assets\Application\DTOs\AssetInput;
use App\Assets\Application\UseCases\CreateAsset;
use App\Assets\Application\UseCases\UpdateAsset;
use App\Assets\Domain\Enums\AssetCategory;
use App\Assets\Domain\Enums\AssetStatus;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Locations\Domain\Repositories\LocationRepository;
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
#[Title('Activo')]
class AssetForm extends Component
{
    public ?int $assetId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $internalCode = '';

    public string $hostname = '';

    public string $category = 'desktop';

    public string $brand = '';

    public string $model = '';

    public string $serialNumber = '';

    public string $locationId = '';

    public string $assignedUserId = '';

    public string $status = 'active';

    public string $operatingSystem = '';

    public string $ipAddress = '';

    public string $purchaseDate = '';

    public string $warrantyUntil = '';

    public string $notes = '';

    /** @var array<int, array{id: int, name: string, is_active: bool}> */
    public array $locationOptions = [];

    /** @var array<int, array{id: int, name: string, is_active: bool}> */
    public array $userOptions = [];

    /** @var array<string, string> */
    public array $categoryOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(?AssetModel $asset = null): void
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

        if ($asset === null) {
            $this->authorize('create', AssetModel::class);
            $this->loadOptions();
            $this->filterOptions();

            return;
        }

        $this->authorize('update', $asset);

        $domain = app(AssetRepository::class)->find($asset->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->assetId = $domain->id;
        $this->internalCode = $domain->internalCode;
        $this->hostname = $domain->hostname ?? '';
        $this->category = $domain->category->value;
        $this->brand = $domain->brand ?? '';
        $this->model = $domain->model ?? '';
        $this->serialNumber = $domain->serialNumber ?? '';
        $this->locationId = (string) $domain->locationId;
        $this->assignedUserId = $domain->assignedUserId === null ? '' : (string) $domain->assignedUserId;
        $this->status = $domain->status->value;
        $this->operatingSystem = $domain->operatingSystem ?? '';
        $this->ipAddress = $domain->ipAddress ?? '';
        $this->purchaseDate = $domain->purchaseDate?->format('Y-m-d') ?? '';
        $this->warrantyUntil = $domain->warrantyUntil?->format('Y-m-d') ?? '';
        $this->notes = $domain->notes ?? '';

        $this->loadOptions();
        $this->filterOptions($domain->locationId, $domain->assignedUserId);

        $this->statusOptions = collect(AssetStatus::cases())
            ->filter(fn (AssetStatus $status) => $domain->status->canTransitionTo($status))
            ->mapWithKeys(fn (AssetStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'internalCode' => [
                'required',
                'string',
                'max:40',
                Rule::unique('assets', 'internal_code')
                    ->where(fn ($query) => $query->where('company_id', $this->companyId))
                    ->ignore($this->assetId),
            ],
            'hostname' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'hostname')
                    ->where(fn ($query) => $query->where('company_id', $this->companyId))
                    ->ignore($this->assetId),
            ],
            'category' => ['required', Rule::enum(AssetCategory::class)],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'serialNumber' => ['nullable', 'string', 'max:120'],
            'locationId' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where('company_id', $this->companyId),
            ],
            'assignedUserId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'operatingSystem' => ['nullable', 'string', 'max:120'],
            'ipAddress' => ['nullable', 'ip', 'max:45'],
            'purchaseDate' => ['nullable', 'date_format:Y-m-d'],
            'warrantyUntil' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:purchaseDate'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $input = new AssetInput(
            internalCode: $validated['internalCode'],
            category: AssetCategory::from($validated['category']),
            locationId: (int) $validated['locationId'],
            status: AssetStatus::from($validated['status']),
            hostname: $this->clean($validated['hostname'] ?? null),
            brand: $this->clean($validated['brand'] ?? null),
            model: $this->clean($validated['model'] ?? null),
            serialNumber: $this->clean($validated['serialNumber'] ?? null),
            assignedUserId: $this->integerOrNull($validated['assignedUserId'] ?? null),
            operatingSystem: $this->clean($validated['operatingSystem'] ?? null),
            ipAddress: $this->clean($validated['ipAddress'] ?? null),
            purchaseDate: $this->dateOrNull($validated['purchaseDate'] ?? null),
            warrantyUntil: $this->dateOrNull($validated['warrantyUntil'] ?? null),
            notes: $this->clean($validated['notes'] ?? null),
        );

        try {
            if ($this->assetId === null) {
                app(CreateAsset::class)->execute($this->companyId, $input, (int) auth()->id());
                Flux::toast(variant: 'success', text: 'Activo creado correctamente.');
            } else {
                app(UpdateAsset::class)->execute(
                    $this->companyId,
                    $this->assetId,
                    $input,
                    (int) auth()->id(),
                );
                Flux::toast(variant: 'success', text: 'Activo actualizado correctamente.');
            }

            $this->redirectRoute('assets.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('assets.livewire.form', [
            'isEditing' => $this->assetId !== null,
            'assignments' => $this->assetId === null
                ? []
                : app(AssetRepository::class)->assignmentHistory($this->assetId),
        ]);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->locationOptions = collect(app(LocationRepository::class)->forCompany($this->companyId))
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'is_active' => $location->isActive,
            ])
            ->all();

        $this->userOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'is_active' => $user->isActive(),
            ])
            ->all();

        $this->categoryOptions = collect(AssetCategory::cases())
            ->mapWithKeys(fn (AssetCategory $category) => [$category->value => $category->label()])
            ->all();

        $this->statusOptions = collect(AssetStatus::cases())
            ->mapWithKeys(fn (AssetStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    private function filterOptions(?int $currentLocationId = null, ?int $currentUserId = null): void
    {
        $this->locationOptions = array_values(array_filter(
            $this->locationOptions,
            fn (array $location) => $location['is_active'] || $location['id'] === $currentLocationId,
        ));

        $this->userOptions = array_values(array_filter(
            $this->userOptions,
            fn (array $user) => $user['is_active'] || $user['id'] === $currentUserId,
        ));
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

    private function dateOrNull(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date === false ? null : $date;
    }
}
