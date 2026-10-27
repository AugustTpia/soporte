<?php

namespace App\Credentials\Presentation\Livewire;

use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Credentials\Application\Contracts\CredentialCipher;
use App\Credentials\Application\DTOs\CredentialInput;
use App\Credentials\Application\UseCases\CreateCredential;
use App\Credentials\Application\UseCases\UpdateCredential;
use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Enums\CredentialKind;
use App\Credentials\Domain\Enums\CredentialStatus;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Credencial')]
class CredentialForm extends Component
{
    public ?int $credentialId = null;

    public ?int $companyId = null;

    public string $companyName = '';

    public string $serviceAccountId = '';

    public string $label = '';

    public string $kind = 'password';

    public string $status = 'active';

    /** @var array<int, array{id: int, label: string}> */
    public array $accountOptions = [];

    /** @var array<string, string> */
    public array $kindOptions = [];

    /** @var array<string, string> */
    public array $statusOptions = [];

    public function mount(?CredentialModel $credential = null): void
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

        if ($credential === null) {
            $this->authorize('create', CredentialModel::class);
            $this->loadOptions();

            $accountId = request()->query('account');

            if (is_numeric($accountId)) {
                $this->serviceAccountId = (string) ((int) $accountId);
                $this->ensureSelectedAccountIsAvailable();
            }

            $this->statusOptions = [
                CredentialStatus::Active->value => CredentialStatus::Active->label(),
            ];

            return;
        }

        $this->authorize('view', $credential);

        $domain = app(CredentialRepository::class)->find($credential->id, $this->companyId);

        if ($domain === null) {
            abort(404);
        }

        $this->credentialId = $domain->id;
        $this->serviceAccountId = (string) $domain->serviceAccountId;
        $this->label = $domain->label;
        $this->kind = $domain->kind->value;
        $this->status = $domain->status->value;

        $this->loadOptions();
        $this->ensureCurrentAccountInOptions($domain);

        $this->statusOptions = collect(CredentialStatus::cases())
            ->filter(fn (CredentialStatus $status) => $domain->status->canTransitionTo($status))
            ->mapWithKeys(fn (CredentialStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function save(): void
    {
        if ($this->companyId === null) {
            abort(403);
        }

        $existing = $this->credentialId !== null
            ? CredentialModel::query()->where('company_id', $this->companyId)->findOrFail($this->credentialId)
            : null;

        if ($existing !== null) {
            $this->authorize('update', $existing);
        }

        $accountId = $existing !== null
            ? (int) $existing->service_account_id
            : (int) $this->serviceAccountId;

        $accountRule = Rule::exists('service_accounts', 'id')
            ->where(fn ($query) => $query->where('company_id', $this->companyId));

        if ($existing === null) {
            $accountRule->whereNot('status', 'revoked');
        }

        $validated = $this->validate([
            'serviceAccountId' => $existing === null
                ? ['required', 'integer', $accountRule]
                : ['nullable'],
            'label' => [
                'required',
                'string',
                'max:120',
                Rule::unique('credential_references', 'label')
                    ->where(fn ($query) => $query->where('service_account_id', $accountId))
                    ->ignore($this->credentialId),
            ],
            'kind' => ['required', Rule::enum(CredentialKind::class)],
            'status' => ['required', Rule::enum(CredentialStatus::class)],
        ]);

        $input = new CredentialInput(
            label: $validated['label'],
            kind: CredentialKind::from($validated['kind']),
            status: CredentialStatus::from($validated['status']),
        );

        try {
            if ($this->credentialId === null) {
                $credential = app(CreateCredential::class)->execute(
                    companyId: $this->companyId,
                    serviceAccountId: $accountId,
                    input: $input,
                    actorId: (int) auth()->id(),
                );

                Flux::toast(variant: 'success', text: 'Credencial creada. Ahora registra su secreto.');
                session()->flash('status', 'Credencial creada. Registra el secreto con tu contraseña actual.');

                $this->redirectRoute('credentials.edit', $credential->id, navigate: true);

                return;
            }

            app(UpdateCredential::class)->execute(
                companyId: $this->companyId,
                credentialId: $this->credentialId,
                input: $input,
                actorId: (int) auth()->id(),
            );

            Flux::toast(variant: 'success', text: 'Credencial actualizada correctamente.');
            $this->redirectRoute('credentials.edit', $this->credentialId, navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        $credential = $this->credentialId !== null ? $this->currentCredential() : null;

        return view('credentials.livewire.form', [
            'credential' => $credential,
            'isEditing' => $this->credentialId !== null,
            'accountLabel' => $this->accountLabel(),
            'canManage' => auth()->user()->can('credentials.manage'),
            'canReveal' => auth()->user()->can('credentials.reveal'),
            'vaultConfigured' => app(CredentialCipher::class)->isConfigured(),
        ]);
    }

    private function currentCredential(): Credential
    {
        $companyId = $this->companyId;
        $credentialId = $this->credentialId;

        if ($companyId === null || $credentialId === null) {
            abort(403);
        }

        $model = CredentialModel::query()
            ->where('company_id', $companyId)
            ->find($credentialId);

        if ($model === null) {
            abort(404);
        }

        $this->authorize('view', $model);

        return app(CredentialRepository::class)->find($credentialId, $companyId)
            ?? abort(404);
    }

    private function loadOptions(): void
    {
        if ($this->companyId === null) {
            return;
        }

        $this->accountOptions = ServiceAccountModel::query()
            ->where('company_id', $this->companyId)
            ->whereNot('status', 'revoked')
            ->orderBy('username')
            ->get(['id', 'username', 'instance'])
            ->map(fn (ServiceAccountModel $account) => [
                'id' => $account->id,
                'label' => $account->username.' · '.$account->instance,
            ])
            ->all();

        $this->kindOptions = collect(CredentialKind::cases())
            ->mapWithKeys(fn (CredentialKind $kind) => [$kind->value => $kind->label()])
            ->all();
    }

    private function ensureCurrentAccountInOptions(Credential $credential): void
    {
        $exists = collect($this->accountOptions)
            ->contains(fn (array $account) => $account['id'] === $credential->serviceAccountId);

        if (! $exists) {
            array_unshift($this->accountOptions, [
                'id' => $credential->serviceAccountId,
                'label' => $credential->accountUsername.' · '.($credential->accountInstance ?? 'Sin instancia'),
            ]);
        }
    }

    private function ensureSelectedAccountIsAvailable(): void
    {
        if ($this->serviceAccountId === '') {
            return;
        }

        $exists = collect($this->accountOptions)
            ->contains(fn (array $account) => (string) $account['id'] === $this->serviceAccountId);

        if (! $exists) {
            $this->serviceAccountId = '';
        }
    }

    private function accountLabel(): string
    {
        if ($this->serviceAccountId === '') {
            return '';
        }

        $option = collect($this->accountOptions)
            ->firstWhere('id', (int) $this->serviceAccountId);

        return $option['label'] ?? '';
    }
}
