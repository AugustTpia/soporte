<?php

namespace App\Credentials\Application\UseCases;

use App\Credentials\Application\Contracts\CredentialCipher;
use App\Credentials\Application\Exceptions\InvalidCurrentPasswordException;
use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Domain\Repositories\CredentialRepository;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Identity\Application\Contracts\PasswordVerifier;
use App\Shared\Application\Contracts\AuditLogger;
use App\Shared\Application\Contracts\TransactionManager;
use App\Shared\Domain\Exceptions\DomainException;

final readonly class SetCredentialSecret
{
    public function __construct(
        private CredentialRepository $credentials,
        private CredentialCipher $cipher,
        private PasswordVerifier $passwords,
        private AuditLogger $audit,
        private TransactionManager $transactions,
    ) {}

    public function execute(
        int $companyId,
        int $credentialId,
        string $plaintext,
        string $currentPassword,
        int $actorId,
    ): int {
        $credential = $this->credentials->find($credentialId, $companyId)
            ?? throw DomainException::because('La credencial no existe en esta empresa.');

        $credential->assertCanStoreSecret();

        $maxLength = Credential::MAX_SECRET_LENGTH;

        if (trim($plaintext) === '') {
            throw DomainException::because('El secreto no puede estar vacío.');
        }

        if (mb_strlen($plaintext) > $maxLength) {
            throw DomainException::because("El secreto no puede superar {$maxLength} caracteres.");
        }

        if (! $this->passwords->verify($actorId, $currentPassword)) {
            $this->audit->log(
                event: 'credential.secret_change_denied',
                description: "Se denegó el cambio de secreto de la credencial {$credential->label}.",
                subjectType: CredentialModel::class,
                subjectId: $credential->id,
                metadata: [
                    'reason' => 'invalid_current_password',
                    'account_username' => $credential->accountUsername,
                ],
                companyId: $companyId,
            );

            throw new InvalidCurrentPasswordException;
        }

        $encrypted = $this->cipher->encrypt($plaintext);

        return $this->transactions->run(function () use (
            $credential,
            $encrypted,
            $companyId,
            $actorId,
        ): int {
            $wasConfigured = $credential->hasSecret;

            $version = $this->credentials->storeSecret(
                credentialId: (int) $credential->id,
                ciphertext: $encrypted['ciphertext'],
                keyId: $encrypted['key_id'],
                actorId: $actorId,
            );

            $this->audit->log(
                event: $wasConfigured ? 'credential.secret_rotated' : 'credential.secret_created',
                description: $wasConfigured
                    ? "Se rotó el secreto de la credencial {$credential->label}."
                    : "Se registró el secreto de la credencial {$credential->label}.",
                subjectType: CredentialModel::class,
                subjectId: $credential->id,
                newValues: [
                    'version' => $version,
                    'key_id' => $encrypted['key_id'],
                    'account_username' => $credential->accountUsername,
                ],
                companyId: $companyId,
            );

            return $version;
        });
    }
}
