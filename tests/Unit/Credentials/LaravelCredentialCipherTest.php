<?php

use App\Credentials\Infrastructure\Encryption\LaravelCredentialCipher;
use App\Shared\Domain\Exceptions\DomainException;

function testCredentialKey(string $byte): string
{
    return 'base64:'.base64_encode(str_repeat($byte, 32));
}

test('credential secrets are encrypted and decrypted with the dedicated key', function () {
    $cipher = new LaravelCredentialCipher(testCredentialKey('a'));

    $encrypted = $cipher->encrypt('S3cret-value');

    expect($encrypted['ciphertext'])->not->toContain('S3cret-value')
        ->and($encrypted['key_id'])->not->toBe('')
        ->and($cipher->decrypt($encrypted['ciphertext'], $encrypted['key_id']))->toBe('S3cret-value');
});

test('previous keys can decrypt existing secrets during rotation', function () {
    $oldCipher = new LaravelCredentialCipher(testCredentialKey('a'));
    $encrypted = $oldCipher->encrypt('legacy-secret');

    $rotatedCipher = new LaravelCredentialCipher(
        currentKey: testCredentialKey('b'),
        previousKeys: [testCredentialKey('a')],
    );

    expect($rotatedCipher->decrypt($encrypted['ciphertext'], $encrypted['key_id']))->toBe('legacy-secret');

    $newCipher = new LaravelCredentialCipher(testCredentialKey('b'));

    expect(fn () => $newCipher->decrypt($encrypted['ciphertext'], $encrypted['key_id']))
        ->toThrow(DomainException::class);
});

test('the vault fails safely without a configured key', function () {
    $cipher = new LaravelCredentialCipher(null);

    expect($cipher->isConfigured())->toBeFalse();

    expect(fn () => $cipher->encrypt('secret'))->toThrow(DomainException::class);
});

test('an invalid vault key fails safely without breaking construction', function () {
    $cipher = new LaravelCredentialCipher('not-a-valid-key');

    expect($cipher->isConfigured())->toBeFalse();

    expect(fn () => $cipher->encrypt('secret'))
        ->toThrow(DomainException::class);
});
