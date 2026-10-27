<?php

namespace App\Credentials\Application\UseCases;

use App\Credentials\Application\Contracts\CredentialCipher;
use App\Credentials\Application\Exceptions\InvalidCurrentPasswordException;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Identity\Application\Contracts\PasswordVerifier;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class RevealCredential
{
    public function __construct(
        private CredentialRepository $credentials,
        private CredentialCipher $cipher,
        private PasswordVerifier $passwords,
        private AuditLogger $audit,
    ) {}

    public function execute(
        int $companyId,
        int $credentialId,
        string $currentPassword,
        int $actorId,
    ): string {
        $credential = $this->credentials->find($credentialId, $companyId)
            ?? throw DomainException::because('La credencial no existe en esta empresa.');

        try {
            $credential->assertCanReveal();
        } catch (DomainException $exception) {
            $this->auditDenied($credentialId, $companyId, $credential->label, $credential->accountUsername, 'not_revealable');

            throw $exception;
        }

        if (! $this->passwords->verify($actorId, $currentPassword)) {
            $this->auditDenied($credentialId, $companyId, $credential->label, $credential->accountUsername, 'invalid_current_password');

            throw new InvalidCurrentPasswordException;
        }

        $payload = $this->credentials->secretPayload($credentialId);

        if ($payload === null) {
            $this->auditDenied($credentialId, $companyId, $credential->label, $credential->accountUsername, 'missing_secret');

            throw DomainException::because('Esta credencial no tiene un secreto almacenado.');
        }

        try {
            $plaintext = $this->cipher->decrypt($payload['ciphertext'], $payload['key_id']);
        } catch (DomainException $exception) {
            $this->auditDenied($credentialId, $companyId, $credential->label, $credential->accountUsername, 'decrypt_failed');

            throw $exception;
        }

        $this->audit->log(
            event: 'credential.revealed',
            description: "Se reveló la credencial {$credential->label}.",
            subjectType: CredentialModel::class,
            subjectId: $credentialId,
            metadata: [
                'account_username' => $credential->accountUsername,
                'secret_version' => $payload['version'],
            ],
            companyId: $companyId,
        );

        return $plaintext;
    }

    private function auditDenied(
        int $credentialId,
        int $companyId,
        string $label,
        string $accountUsername,
        string $reason,
    ): void {
        $this->audit->log(
            event: 'credential.reveal_denied',
            description: "Se denegó el acceso a la credencial {$label}.",
            subjectType: CredentialModel::class,
            subjectId: $credentialId,
            metadata: [
                'reason' => $reason,
                'account_username' => $accountUsername,
            ],
            companyId: $companyId,
        );
    }
}
