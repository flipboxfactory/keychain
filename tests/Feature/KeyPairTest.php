<?php

declare(strict_types=1);

use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;

it('generates a matching key and certificate', function () {
    $record = new OpenSSL([
        'commonName' => 'keychain.test',
        'description' => 'Test key pair',
    ])->create();

    expect(openssl_x509_check_private_key($record->getDecryptedCertificate(), $record->getDecryptedKey()))->toBeTrue()
        ->and($record->class)->toBe(OpenSSL::class)
        ->and($record->description)->toBe('Test key pair')
        ->and(openssl_x509_parse($record->getDecryptedCertificate())['subject']['CN'])->toBe('keychain.test');
});

// Blank DN fields (e.g. left empty in the CP form) used to make openssl_csr_new() throw
it('skips blank distinguished name fields', function () {
    $record = new OpenSSL([
        'countryName' => '',
        'commonName' => 'keychain.test',
        'emailAddress' => null,
    ])->create();

    // Omitted fields may be filled from the system openssl.cnf, so only assert what we set
    expect(openssl_x509_parse($record->getDecryptedCertificate())['subject']['CN'])->toBe('keychain.test')
        ->and(openssl_x509_check_private_key($record->getDecryptedCertificate(), $record->getDecryptedKey()))->toBeTrue();
});

it('honours the digest, key size and expiry', function (string $digest, int $bits, string $signatureType) {
    $record = new OpenSSL([
        'digestAlgorithm' => $digest,
        'keyBits' => $bits,
        'daysExpiry' => 30,
    ])->create();

    $certificate = openssl_x509_parse($record->getDecryptedCertificate());

    expect($certificate['signatureTypeSN'])->toBe($signatureType)
        ->and(openssl_pkey_get_details(openssl_pkey_get_private($record->getDecryptedKey()))['bits'])->toBe($bits)
        ->and($certificate['validTo_time_t'] - $certificate['validFrom_time_t'])->toEqualWithDelta(30 * 86400, 60);
})->with([
    'sha256 / 2048' => ['sha256', 2048, 'RSA-SHA256'],
    'sha512 / 3072' => ['sha512', 3072, 'RSA-SHA512'],
]);

it('stores generation options but no key material in settings', function () {
    $record = new OpenSSL(['commonName' => 'keychain.test'])->create();

    expect($record->settings)->toMatchArray(['commonName' => 'keychain.test', 'keyBits' => 2048])
        ->and(json_encode($record->settings))->not->toContain('PRIVATE KEY');
});

it('wraps a key pair the user brings', function () {
    $generated = new OpenSSL()->create();

    $record = new Byok([
        'key' => $generated->getDecryptedKey(),
        'certificate' => $generated->getDecryptedCertificate(),
        'description' => 'Bring your own',
    ])->create();

    expect($record->getDecryptedKey())->toBe($generated->getDecryptedKey())
        ->and($record->getDecryptedCertificate())->toBe($generated->getDecryptedCertificate())
        ->and($record->class)->toBe(Byok::class)
        ->and($record->description)->toBe('Bring your own')
        ->and($record->settings)->toBe(['description' => 'Bring your own']);
});
