<?php

namespace App\Credentials\Application\UseCases;

use App\Credentials\Application\DTOs\CredentialInput;
use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class UpdateCredential
{
    public function __construct(
        private CredentialRepository $credentials,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $credentialId,
        CredentialInput $input,
        int $actorId,
    ): Credential {
        $credential = $this->credentials->find($credentialId, $companyId)
            ?? throw DomainException::because('La credencial no existe en esta empresa.');

        $label = trim($input->label);

        if ($this->credentials->labelExists($credential->serviceAccountId, $label, $credential->id)) {
            throw DomainException::because('Ya existe una credencial con esa etiqueta para la cuenta.');
        }

        $before = $this->auditValues($credential);
        $oldStatus = $credential->status;

        $credential->updateDetails($label, $input->kind);
        $statusChanged = $oldStatus !== $input->status;
        $credential->changeStatus($input->status);

        return $this->transactions->run(function () use (
            $credential,
            $companyId,
            $before,
            $oldStatus,
            $statusChanged,
        ): Credential {
            $saved = $this->credentials->save($credential);

            if ($statusChanged) {
                $this->audit->log(
                    event: 'credential.status_changed',
                    description: "Se cambió el estado de la credencial {$saved->label}.",
                    subjectType: CredentialModel::class,
                    subjectId: $saved->id,
                    oldValues: ['status' => $oldStatus->value],
                    newValues: ['status' => $saved->status->value],
                    companyId: $companyId,
                );
            }

            $this->audit->log(
                event: 'credential.updated',
                description: "Se actualizó la credencial {$saved->label}.",
                subjectType: CredentialModel::class,
                subjectId: $saved->id,
                oldValues: $before,
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
