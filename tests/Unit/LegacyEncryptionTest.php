<?php

declare(strict_types=1);

use flipbox\keychain\security\LegacyDecryptionException;
use flipbox\keychain\security\LegacyEncryption;

it('decrypts what Craft 5 encrypted', function () {
    $fixture = craft5Fixture();

    expect(LegacyEncryption::decrypt(base64_decode($fixture['encryptedKey']), $fixture['securityKey']))->toBe($fixture['key'])
        ->and(LegacyEncryption::decrypt(base64_decode($fixture['encryptedCertificate']), $fixture['securityKey']))->toBe($fixture['certificate']);
});

it('rejects the wrong security key', function () {
    LegacyEncryption::decrypt(base64_decode(craft5Fixture()['encryptedKey']), 'not-the-security-key');
})->throws(LegacyDecryptionException::class, 'failed authentication');

it('rejects an empty security key', function () {
    LegacyEncryption::decrypt(base64_decode(craft5Fixture()['encryptedKey']), '');
})->throws(LegacyDecryptionException::class);

it('rejects tampered data', function (int $offset) {
    $fixture = craft5Fixture();
    $data = base64_decode($fixture['encryptedCertificate']);
    $data[$offset] = chr(ord($data[$offset]) ^ 1);

    LegacyEncryption::decrypt($data, $fixture['securityKey']);
})->with([
    'salt' => 0,
    'mac' => 20,
    'iv' => 16 + 64,
    'ciphertext' => -1,
])->throws(LegacyDecryptionException::class);

it('rejects truncated data', function () {
    $fixture = craft5Fixture();

    LegacyEncryption::decrypt(substr(base64_decode($fixture['encryptedKey']), 0, 96), $fixture['securityKey']);
})->throws(LegacyDecryptionException::class);

it('never puts the security key or plaintext in its messages', function () {
    $fixture = craft5Fixture();

    try {
        LegacyEncryption::decrypt(base64_decode($fixture['encryptedKey']), 'secret-security-key-value');
    } catch (LegacyDecryptionException $e) {
        expect($e->getMessage())->not->toContain('secret-security-key-value')
            ->and($e->getMessage())->not->toContain($fixture['key']);

        return;
    }

    $this->fail('Decryption with the wrong key succeeded.');
});
