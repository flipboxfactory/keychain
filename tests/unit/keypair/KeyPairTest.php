<?php

namespace flipbox\keychain\tests\unit\keypair;

use Codeception\Test\Unit;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;

class KeyPairTest extends Unit
{
    public function testOpenSslCreatesMatchingKeyAndCertificate(): void
    {
        $record = (new OpenSSL([
            'commonName' => 'keychain.test',
            'description' => 'Test key pair',
        ]))->create();

        $this->assertTrue(openssl_x509_check_private_key($record->certificate, $record->key));
        $this->assertSame(OpenSSL::class, $record->class);
        $this->assertSame('Test key pair', $record->description);
        $this->assertSame('keychain.test', openssl_x509_parse($record->certificate)['subject']['CN']);
    }

    /**
     * Blank DN fields (e.g. left empty in the CP form) used to make openssl_csr_new() throw.
     */
    public function testOpenSslSkipsBlankDistinguishedNameFields(): void
    {
        $record = (new OpenSSL([
            'countryName' => '',
            'commonName' => 'keychain.test',
            'emailAddress' => null,
        ]))->create();

        // Omitted fields may be filled from the system openssl.cnf, so only assert what we set
        $this->assertSame('keychain.test', openssl_x509_parse($record->certificate)['subject']['CN']);
        $this->assertTrue(openssl_x509_check_private_key($record->certificate, $record->key));
    }

    /**
     * @dataProvider optionsProvider
     */
    public function testOpenSslHonoursOptions(string $digest, int $bits, string $signatureType): void
    {
        $record = (new OpenSSL([
            'digestAlgorithm' => $digest,
            'keyBits' => $bits,
            'daysExpiry' => 30,
        ]))->create();

        $certificate = openssl_x509_parse($record->certificate);
        $this->assertSame($signatureType, $certificate['signatureTypeSN']);
        $this->assertSame($bits, openssl_pkey_get_details(openssl_pkey_get_private($record->key))['bits']);
        $this->assertEqualsWithDelta(30 * 86400, $certificate['validTo_time_t'] - $certificate['validFrom_time_t'], 60);
    }

    public function optionsProvider(): array
    {
        return [
            'sha256 / 2048' => ['sha256', 2048, 'RSA-SHA256'],
            'sha512 / 3072' => ['sha512', 3072, 'RSA-SHA512'],
        ];
    }

    public function testByokWrapsProvidedKeyPair(): void
    {
        $generated = (new OpenSSL())->create();

        $record = (new Byok([
            'key' => $generated->key,
            'certificate' => $generated->certificate,
            'description' => 'Bring your own',
        ]))->create();

        $this->assertSame($generated->key, $record->key);
        $this->assertSame($generated->certificate, $record->certificate);
        $this->assertSame(Byok::class, $record->class);
        $this->assertSame('Bring your own', $record->description);
    }
}
