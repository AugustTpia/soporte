<?php

namespace App\Identity\Presentation\Livewire\Users;

use App\Companies\Domain\Repositories\CompanyRepository;
use App\Departments\Domain\Repositories\DepartmentRepository;
use App\Identity\Application\DTOs\UserInput;
use App\Identity\Application\UseCases\CreateUser;
use App\Identity\Application\UseCases\UpdateUser;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Usuario')]
class UserForm extends Component
{
    public ?int $userId = null;

    public string $companyId = '';

    public string $name = '';

    public string $email = '';

    public string $jobTitle = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var array<int, string> */
    public array $roles = [];

    /** @var array<int, int|string> */
    public array $departmentIds = [];

    public bool $actorIsSuperAdmin = false;

    public ?int $actorCompanyId = null;

    /** @var array<int, array{id: int, name: string}> */
    public array $companyOptions = [];

    /** @var array<int, array{id: int, name: string}> */
    public array $departmentOptions = [];

    /** @var array<string, string> */
    public array $roleOptions = [];

    /** @var array<string, string> */
    public array $roleDescriptions = [];

    public function mount(?UserModel $user = null): void
    {
        $this->actorIsSuperAdmin = auth()->user()->isSuperAdmin();
        $this->actorCompanyId = app(TenantContext::class)->companyId();

        if ($user === null) {
            $this->authorize('create', UserModel::class);

            if (! $this->actorIsSuperAdmin && $this->actorCompanyId === null) {
                $this->redirectRoute('dashboard', navigate: true);

                return;
            }

            $this->companyId = $this->actorCompanyId === null ? '' : (string) $this->actorCompanyId;
        } else {
            $this->authorize('update', $user);

            $domain = app(UserRepository::class)->find($user->id);

            if ($domain === null) {
                abort(404);
            }

            if (! $this->actorIsSuperAdmin && $domain->companyId !== $this->actorCompanyId) {
                abort(403);
            }

            $this->userId = $domain->id;
            $this->companyId = $domain->companyId === null ? '' : (string) $domain->companyId;
            $this->name = $domain->name;
            $this->email = $domain->email;
            $this->jobTitle = $domain->jobTitle ?? '';
            $this->phone = $domain->phone ?? '';
            $this->roles = $domain->roles;
            $this->departmentIds = $domain->departmentIds;
        }

        $this->loadCompanyOptions();
        $this->loadRoleOptions();
        $this->loadDepartments();
    }

    public function updatedCompanyId(): void
    {
        $this->loadDepartments();
        $this->departmentIds = [];
    }

    public function save(): void
    {
        $companyId = $this->actorIsSuperAdmin
            ? ($this->companyId === '' ? null : (int) $this->companyId)
            : $this->actorCompanyId;

        $allowedRoles = array_map(
            fn (RoleName $role) => $role->value,
            $this->actorIsSuperAdmin ? RoleName::cases() : RoleName::companyAssignable(),
        );

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'companyId' => ['nullable', Rule::exists('companies', 'id')->where('status', 'active')],
            'jobTitle' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($allowedRoles)],
            'departmentIds' => ['array'],
            'departmentIds.*' => ['integer'],
        ];

        if ($this->userId === null) {
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'string', 'confirmed', Password::defaults()];
        }

        $validated = $this->validate($rules);

        if ($companyId === null && $validated['departmentIds'] !== []) {
            $this->addError('departmentIds', 'Selecciona una empresa antes de asignar departamentos.');

            return;
        }

        $input = new UserInput(
            name: $validated['name'],
            email: $validated['email'],
            companyId: $companyId,
            jobTitle: $this->clean($validated['jobTitle'] ?? null),
            phone: $this->clean($validated['phone'] ?? null),
            password: $this->password !== '' ? $this->password : null,
            roles: $validated['roles'],
            departmentIds: array_map('intval', $validated['departmentIds']),
        );

        try {
            if ($this->userId === null) {
                app(CreateUser::class)->execute(
                    (int) auth()->id(),
                    $this->actorIsSuperAdmin,
                    $this->actorCompanyId,
                    $input,
                );

                Flux::toast(variant: 'success', text: 'Usuario creado correctamente.');
            } else {
                app(UpdateUser::class)->execute(
                    (int) auth()->id(),
                    $this->actorIsSuperAdmin,
                    $this->actorCompanyId,
                    $this->userId,
                    $input,
                );

                Flux::toast(variant: 'success', text: 'Usuario actualizado correctamente.');
            }

            $this->redirectRoute('users.index', navigate: true);
        } catch (DomainException $exception) {
            $this->addError('form', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('identity.livewire.users.form', [
            'isEditing' => $this->userId !== null,
        ]);
    }

    private function loadCompanyOptions(): void
    {
        if (! $this->actorIsSuperAdmin) {
            $company = auth()->user()->company;

            $this->companyOptions = $company === null
                ? []
                : [['id' => $company->id, 'name' => $company->name]];

            return;
        }

        $this->companyOptions = collect(app(CompanyRepository::class)->activeCompanies())
            ->map(fn ($company) => ['id' => $company->id, 'name' => $company->name])
            ->all();
    }

    private function loadRoleOptions(): void
    {
        $roles = $this->actorIsSuperAdmin ? RoleName::cases() : RoleName::companyAssignable();

        $this->roleOptions = collect($roles)
            ->mapWithKeys(fn (RoleName $role) => [$role->value => $role->label()])
            ->all();

        $this->roleDescriptions = collect($roles)
            ->mapWithKeys(fn (RoleName $role) => [$role->value => $role->description()])
            ->all();
    }

    private function loadDepartments(): void
    {
        $companyId = $this->companyId === '' ? null : (int) $this->companyId;

        if ($companyId === null) {
            $this->departmentOptions = [];

            return;
        }

        $this->departmentOptions = collect(app(DepartmentRepository::class)->activeForCompany($companyId))
            ->map(fn ($department) => ['id' => $department->id, 'name' => $department->name])
            ->all();
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
