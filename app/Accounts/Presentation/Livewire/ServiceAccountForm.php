<?php

namespace App\Accounts\Presentation\Livewire;

use App\Accounts\Application\DTOs\ServiceAccountInput;
use App\Accounts\Application\UseCases\CreateServiceAccount;
use App\Accounts\Application\UseCases\LinkServiceAccountAsset;
use App\Accounts\Application\UseCases\TransferServiceAccountAsset;
use App\Accounts\Application\UseCases\UnlinkServiceAccountAsset;
use App\Accounts\Application\UseCases\UpdateServiceAccount;
use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Enums\ServiceType;
use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Assets\Domain\Repositories\AssetRepository;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cuenta')]
class ServiceAccountForm extends Component
{
    public ?int $accountId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $service = 'nextcloud';

    public string $username = '';

    public string $instance = '';

    public string $ownerName = '';

    public string $responsibleUserId = '';

    public string $status = 'active';

    public string $notes = '';

    public string $linkAssetId = '';

    public string $transferFromAssetId = '';

    public string $transferToAssetId = '';

    /** @var array<int, array{id: int, name: string, is_active: bool}> */
    public array $responsibleOptions = [];

    /** @var array<int, array{id: int, label: string, status: string, is_linkable: bool}> */
    public array $assetOptions = [];

    /** @var array<string, string> */
    public array $serviceOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(?ServiceAccountModel $account = null): void
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

        if ($account === null) {
            $this->authorize('create', ServiceAccountModel::class);
            $this->loadOptions();
            $this->filterResponsibleOptions();

            return;
        }

        $this->authorize('update', $account);

        $domain = app(ServiceAccountRepository::class)->find($account->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->accountId = $domain->id;
        $this->service = $domain->service->value;
        $this->username = $domain->username;
        $this->instance = $domain->instance;
        $this->ownerName = $domain->ownerName ?? '';
        $this->responsibleUserId = $domain->responsibleUserId === null ? '' : (string) $domain->responsibleUserId;
        $this->status = $domain->status->value;
        $this->notes = $domain->notes ?? '';

        $this->loadOptions();
        $this->filterResponsibleOptions($domain->responsibleUserId);

        $this->statusOptions = collect(ServiceAccountStatus::cases())
            ->filter(fn (ServiceAccountStatus $status) => $domain->status->canTransitionTo($status))
            ->mapWithKeys(fn (ServiceAccountStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'service' => ['required', Rule::enum(ServiceType::class)],
            'username' => [
                'required',
                'string',
                'max:150',
                Rule::unique('service_accounts', 'username')
                    ->where(fn ($query) => $query
                        ->where('company_id', $this->companyId)
                        ->where('service', $this->service)
                        ->where('instance', mb_strtolower(trim($this->instance))))
                    ->ignore($this->accountId),
            ],
            'instance' => ['required', 'string', 'max:255'],
            'ownerName' => ['nullable', 'string', 'max:150'],
            'responsibleUserId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->companyId),
            ],
            'status' => ['required', Rule::enum(ServiceAccountStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $input = new ServiceAccountInput(
            service: ServiceType::from($validated['service']),
            username: $validated['username'],
            instance: $validated['instance'],
            ownerName: $this->clean($validated['ownerName'] ?? null),
            status: ServiceAccountStatus::from($validated['status']),
            responsibleUserId: $this->integerOrNull($validated['responsibleUserId'] ?? null),
            notes: $this->clean($validated['notes'] ?? null),
        );

        try {
            if ($this->accountId === null) {
                app(CreateServiceAccount::class)->execute($this->companyId, $input, (int) auth()->id());
                Flux::toast(variant: 'success', text: 'Cuenta creada correctamente.');
            } else {
                app(UpdateServiceAccount::class)->execute(
                    $this->companyId,
                    $this->accountId,
                    $input,
                    (int) auth()->id(),
                );
                Flux::toast(variant: 'success', text: 'Cuenta actualizada correctamente.');
            }

            $this->redirectRoute('accounts.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function linkAsset(): void
    {
        $account = $this->currentAccount();

        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'linkAssetId' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')->where('company_id', $this->companyId),
            ],
        ]);

        try {
            app(LinkServiceAccountAsset::class)->execute(
                $this->companyId,
                (int) $account->id,
                (int) $validated['linkAssetId'],
                (int) auth()->id(),
            );

            $this->linkAssetId = '';
            Flux::toast(variant: 'success', text: 'Equipo vinculado correctamente.');
        } catch (DomainException $exception) {
            $this->addError('linkAssetId', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function unlinkAsset(int $linkId): void
    {
        $account = $this->currentAccount();

        if ($this->companyId === null) {
            abort(403);
        }

        try {
            app(UnlinkServiceAccountAsset::class)->execute(
                $this->companyId,
                (int) $account->id,
                $linkId,
                (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Equipo desvinculado correctamente.');
        } catch (DomainException $exception) {
            $this->addError('links', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function transferAsset(): void
    {
        $account = $this->currentAccount();

        if ($this->companyId === null) {
            abort(403);
        }

        $validated = $this->validate([
            'transferFromAssetId' => ['required', 'integer'],
            'transferToAssetId' => [
                'required',
                'integer',
                'different:transferFromAssetId',
                Rule::exists('assets', 'id')->where('company_id', $this->companyId),
            ],
        ]);

        try {
            app(TransferServiceAccountAsset::class)->execute(
                $this->companyId,
                (int) $account->id,
                (int) $validated['transferFromAssetId'],
                (int) $validated['transferToAssetId'],
                (int) auth()->id(),
            );

            $this->transferFromAssetId = '';
            $this->transferToAssetId = '';
            Flux::toast(variant: 'success', text: 'Cuenta transferida al nuevo equipo.');
        } catch (DomainException $exception) {
            $this->addError('transferToAssetId', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $account = $this->accountId !== null ? $this->currentAccount() : null;

        return view('accounts.livewire.form', [
            'account' => $account,
            'isEditing' => $this->accountId !== null,
            'links' => $account !== null ? $account->links : [],
            'activeLinks' => $account !== null ? $account->activeLinks() : [],
        ]);
    }

    private function currentAccount(): ServiceAccount
    {
        $companyId = $this->companyId;
        $accountId = $this->accountId;

        if ($companyId === null || $accountId === null) {
            abort(403);
        }

        $model = ServiceAccountModel::query()
            ->where('company_id', $companyId)
            ->find($accountId);

        if ($model === null) {
            abort(404);
        }

        $this->authorize('update', $model);

        return app(ServiceAccountRepository::class)->find($accountId, $companyId)
            ?? abort(404);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->responsibleOptions = collect(app(UserRepository::class)->forCompany($this->companyId))
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'is_active' => $user->isActive(),
            ])
            ->all();

        $this->assetOptions = collect(app(AssetRepository::class)->forCompany($this->companyId))
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'label' => $asset->internalCode.($asset->hostname !== null ? ' · '.$asset->hostname : ''),
                'status' => $asset->status->label(),
                'is_linkable' => $asset->status->isLinkable(),
            ])
            ->all();

        $this->serviceOptions = collect(ServiceType::cases())
            ->mapWithKeys(fn (ServiceType $type) => [$type->value => $type->label()])
            ->all();

        $this->statusOptions = collect(ServiceAccountStatus::cases())
            ->mapWithKeys(fn (ServiceAccountStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    private function filterResponsibleOptions(?int $currentUserId = null): void
    {
        $this->responsibleOptions = array_values(array_filter(
            $this->responsibleOptions,
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
}
