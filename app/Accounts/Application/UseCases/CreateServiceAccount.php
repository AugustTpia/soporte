<?php

namespace App\Accounts\Application\UseCases;

use App\Accounts\Application\DTOs\ServiceAccountInput;
use App\Accounts\Domain\Entities\ServiceAccount;
use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Accounts\Infrastructure\Persistence\Eloquent\Models\ServiceAccountModel;
use App\Identity\Domain\Repositories\UserRepository;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class CreateServiceAccount
{
    public function __construct(
        private ServiceAccountRepository $accounts,
        private UserRepository $users,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(int $companyId, ServiceAccountInput $input, int $actorId): ServiceAccount
    {
        $this->assertResponsibleUser($companyId, $input->responsibleUserId);

        $username = trim($input->username);
        $instance = mb_strtolower(trim($input->instance));

        if ($this->accounts->identityExists($companyId, $input->service, $instance, $username)) {
            throw DomainException::because('Ya existe una cuenta con ese usuario en el mismo servicio e instancia.');
        }

        $account = ServiceAccount::create(
            companyId: $companyId,
            service: $input->service,
            username: $username,
            instance: $instance,
            ownerName: $input->ownerName,
            responsibleUserId: $input->responsibleUserId,
            notes: $input->notes,
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($account, $companyId): ServiceAccount {
            $saved = $this->accounts->save($account);

            $this->audit->log(
                event: 'service_account.created',
                description: "Se creó la cuenta {$saved->service->label()} {$saved->username}.",
                subjectType: ServiceAccountModel::class,
                subjectId: $saved->id,
                newValues: $this->auditValues($saved),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    private function assertResponsibleUser(int $companyId, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $user = $this->users->find($userId);

        if ($user === null || $user->companyId !== $companyId) {
            throw DomainException::because('El responsable seleccionado no pertenece a esta empresa.');
        }

        if (! $user->isActive()) {
            throw DomainException::because('El responsable seleccionado está inactivo.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ServiceAccount $account): array
    {
        return [
            'service' => $account->service->value,
            'username' => $account->username,
            'instance' => $account->instance,
            'owner_name' => $account->ownerName,
            'status' => $account->status->value,
            'responsible_user_id' => $account->responsibleUserId,
            'responsible_user_name' => $account->responsibleUserName,
            'notes' => $account->notes,
        ];
    }
}
