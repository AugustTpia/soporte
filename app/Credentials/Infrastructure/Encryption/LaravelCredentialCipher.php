<?php

namespace App\Credentials\Infrastructure\Encryption;

use App\Credentials\Application\Contracts\CredentialCipher;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

final class LaravelCredentialCipher implements CredentialCipher
{
    /** @var array<string, Encrypter> */
    private array $encrypters = [];

    private ?string $currentKeyId = null;

    private ?string $configurationError = null;

    /**
     * @param  array<int, string>  $previousKeys
     */
    public function __construct(?string $currentKey, array $previousKeys = [])
    {
        if ($currentKey !== null && trim($currentKey) !== '') {
            try {
                $encrypter = $this->makeEncrypter($currentKey);
                $this->currentKeyId = $this->keyId($encrypter);
                $this->encrypters[$this->currentKeyId] = $encrypter;
            } catch (InvalidArgumentException $exception) {
                $this->configurationError = $exception->getMessage();
            }
        }

        foreach ($previousKeys as $previousKey) {
            if (trim($previousKey) === '') {
                continue;
            }

            try {
                $encrypter = $this->makeEncrypter($previousKey);
                $this->encrypters[$this->keyId($encrypter)] = $encrypter;
            } catch (InvalidArgumentException $exception) {
                $this->configurationError ??= $exception->getMessage();
            }
        }
    }

    public function isConfigured(): bool
    {
        return $this->currentKeyId !== null;
    }

    public function encrypt(string $plaintext): array
    {
        $keyId = $this->currentKeyId;

        if ($keyId === null) {
            throw DomainException::because($this->configurationError !== null
                ? 'La clave de la bóveda de credenciales no es válida. Revisa CREDENTIALS_ENCRYPTION_KEY.'
                : 'La bóveda de credenciales no está configurada. Define CREDENTIALS_ENCRYPTION_KEY.');
        }

        return [
            'ciphertext' => $this->encrypters[$keyId]->encryptString($plaintext),
            'key_id' => $keyId,
        ];
    }

    public function decrypt(string $ciphertext, string $keyId): string
    {
        $encrypter = $this->encrypters[$keyId] ?? null;

        if ($encrypter === null) {
            throw DomainException::because(
                'No hay una clave disponible para descifrar esta credencial. Revisa la configuración de claves.',
            );
        }

        try {
            return $encrypter->decryptString($ciphertext);
        } catch (DecryptException) {
            throw DomainException::because(
                'No se pudo descifrar la credencial. La clave o el contenido cifrado no son válidos.',
            );
        }
    }

    private function makeEncrypter(string $encodedKey): Encrypter
    {
        $encodedKey = trim($encodedKey);
        $encodedKey = str_starts_with($encodedKey, 'base64:')
            ? substr($encodedKey, 7)
            : $encodedKey;

        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new InvalidArgumentException(
                'CREDENTIALS_ENCRYPTION_KEY debe ser una clave de 32 bytes codificada en base64.',
            );
        }

        return new Encrypter($key, 'aes-256-gcm');
    }

    private function keyId(Encrypter $encrypter): string
    {
        return substr(hash('sha256', $encrypter->getKey()), 0, 16);
    }
}
