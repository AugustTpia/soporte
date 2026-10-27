<?php

namespace App\Credentials\Application\UseCases;

use App\Accounts\Domain\Enums\ServiceAccountStatus;
use App\Accounts\Domain\Repositories\ServiceAccountRepository;
use App\Credentials\Application\DTOs\CredentialInput;
use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class CreateCredential
{
    public function __construct(
        private CredentialRepository $credentials,
        private ServiceAccountRepository $accounts,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $serviceAccountId,
        CredentialInput $input,
        int $actorId,
    ): Credential {
        $account = $this->accounts->find($serviceAccountId, $companyId)
            ?? throw DomainException::because('La cuenta de servicio no existe en esta empresa.');

        if ($account->status === ServiceAccountStatus::Revoked) {
            throw DomainException::because('No se pueden crear credenciales para una cuenta revocada.');
        }

        $label = trim($input->label);

        if ($this->credentials->labelExists($serviceAccountId, $label)) {
            throw DomainException::because('Ya existe una credencial con esa etiqueta para la cuenta.');
        }

        $credential = Credential::create(
            companyId: $companyId,
            serviceAccountId: $serviceAccountId,
            accountUsername: $account->username,
            accountInstance: $account->instance,
            label: $label,
            kind: $input->kind,
            createdBy: $actorId,
        );

        return $this->transactions->run(function () use ($credential, $companyId): Credential {
            $saved = $this->credentials->save($credential);

            $this->audit->log(
                event: 'credential.created',
                description: "Se creó la credencial {$saved->label} para la cuenta {$saved->accountUsername}.",
                subjectType: CredentialModel::class,
                subjectId: $saved->id,
                newValues: $this->auditValues($saved),
                companyId: $companyId,
            );

            return $saved;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Credential $credential): array
    {
        return [
            'service_account_id' => $credential->serviceAccountId,
            'account_username' => $credential->accountUsername,
            'label' => $credential->label,
            'kind' => $credential->kind->value,
            'status' => $credential->status->value,
            'has_secret' => $credential->hasSecret,
        ];
    }
}
