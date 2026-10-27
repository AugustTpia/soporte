<?php

namespace App\Credentials\Application\Contracts;

interface CredentialCipher
{
    public function isConfigured(): bool;

    /**
     * @return array{ciphertext: string, key_id: string}
     */
    public function encrypt(string $plaintext): array;

    public function decrypt(string $ciphertext, string $keyId): string;
}
