<?php

namespace App\Identity\Application\UseCases;

use App\Identity\Domain\Entities\User;
use App\Identity\Domain\Enums\RoleName;
use App\Identity\Domain\Repositories\UserRepository;
use App\Identity\Infrastructure\Persistence\Eloquent\User as UserModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class ToggleUserStatus
{
    public function __construct(
        private UserRepository $users,
        private AuditLogger $audit,
    ) {}

    public function execute(
        int $actorId,
        bool $actorIsSuperAdmin,
        ?int $actorCompanyId,
        int $userId,
    ): User {
        if ($actorId === $userId) {
            throw DomainException::because('No puedes cambiar el estado de tu propia cuenta.');
        }

        $target = $this->users->find($userId)
            ?? throw DomainException::because('El usuario no existe.');

        if (! $actorIsSuperAdmin) {
            if ($target->companyId !== $actorCompanyId) {
                throw DomainException::because('No puedes administrar usuarios de otra empresa.');
            }

            if ($target->isSuperAdmin() || in_array(RoleName::CompanyAdmin->value, $target->roles, true)) {
                throw DomainException::because('Solo un superadministrador puede cambiar el estado de este usuario.');
            }
        }

        if ($target->isSuperAdmin() && $this->users->countSuperAdmins() <= 1) {
            throw DomainException::because('No puedes desactivar al último superadministrador.');
        }

        $before = $target->status->value;

        $target->isActive() ? $target->deactivate() : $target->activate();
        $saved = $this->users->save($target);

        $this->audit->log(
            event: 'user.status_changed',
            description: "Se cambió el estado del usuario {$saved->name} a {$saved->status->label()}.",
            subjectType: UserModel::class,
            subjectId: $saved->id,
            oldValues: ['status' => $before],
            newValues: ['status' => $saved->status->value],
            companyId: $saved->companyId,
        );

        return $saved;
    }
}
