<?php

namespace App\Identity\Presentation\Livewire\Users;

use App\Identity\Application\UseCases\SearchUsers;
use App\Identity\Application\UseCases\ToggleUserStatus;
use App\Identity\Domain\Entities\User as UserEntity;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
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
#[Title('Usuarios')]
class UserIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $role = '';

    public function mount(): void
    {
        $this->authorize('viewAny', UserModel::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $this->authorize('toggleStatus', UserModel::query()->findOrFail($userId));

        $tenant = app(TenantContext::class);

        try {
            app(ToggleUserStatus::class)->execute(
                (int) auth()->id(),
                $tenant->isSuperAdmin(),
                $tenant->companyId(),
                $userId,
            );

            Flux::toast(variant: 'success', text: 'Estado del usuario actualizado.');
        } catch (DomainException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }
    }

    public function canEdit(UserEntity $user): bool
    {
        $auth = auth()->user();

        if ($auth->isSuperAdmin()) {
            return true;
        }

        return $auth->can('users.update')
            && $auth->id !== $user->id
            && $auth->company_id === $user->companyId
            && ! $user->isSuperAdmin()
            && ! in_array(RoleName::CompanyAdmin->value, $user->roles, true);
    }

    public function canToggle(UserEntity $user): bool
    {
        $auth = auth()->user();

        if ($auth->id === $user->id) {
            return false;
        }

        if ($auth->isSuperAdmin()) {
            return true;
        }

        return $auth->can('users.toggle')
            && $auth->company_id === $user->companyId
            && ! $user->isSuperAdmin()
            && ! in_array(RoleName::CompanyAdmin->value, $user->roles, true);
    }

    public function render(): View
    {
        $tenant = app(TenantContext::class);
        $companyId = $tenant->companyId();

        $result = app(SearchUsers::class)->execute(
            $companyId,
            $this->search !== '' ? $this->search : null,
            $this->getPage(),
            10,
            $this->status === '' ? null : UserStatus::from($this->status),
            $this->role !== '' ? $this->role : null,
        );

        $users = new LengthAwarePaginator(
            $result->items,
            $result->total,
            $result->perPage,
            $result->currentPage,
            ['path' => route('users.index')],
        );

        return view('identity.livewire.users.index', [
            'users' => $users,
            'global' => $companyId === null,
            'roles' => collect(RoleName::cases())->mapWithKeys(fn (RoleName $role) => [$role->value => $role->label()])->all(),
        ]);
    }
}
