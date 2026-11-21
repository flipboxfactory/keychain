<?php

declare(strict_types=1);

namespace flipbox\keychain\keypair;

use CraftCms\Cms\Component\Component;
use flipbox\keychain\records\KeyChainRecord;
use RuntimeException;

/**
 * Generates an RSA key pair with a self-signed certificate.
 */
class OpenSSL extends Component implements KeyPairInterface
{
    /**
     * Distinguished name fields, in the order OpenSSL prompts for them.
     */
    public const array DN_FIELDS = [
        'countryName',
        'stateOrProvinceName',
        'localityName',
        'organizationName',
        'organizationalUnitName',
        'commonName',
        'emailAddress',
    ];

    public string $digestAlgorithm = 'sha256';

    public int $keyBits = 2048;

    public int $daysExpiry = 365;

    public ?string $description = 'What is this key for?';

    public ?string $countryName = null;

    public ?string $stateOrProvinceName = null;

    public ?string $localityName = null;

    public ?string $organizationName = null;

    public ?string $organizationalUnitName = null;

    public ?string $commonName = null;

    public ?string $emailAddress = null;

    public function create(): KeyChainRecord
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => $this->keyBits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]) ?: throw new RuntimeException('OpenSSL couldn’t generate a private key.');

        // OpenSSL rejects empty DN entries, so leave blank fields out instead of failing
        $dn = array_filter($this->distinguishedName(), fn ($value) => $value !== null && $value !== '');
        $options = ['digest_alg' => $this->digestAlgorithm];

        $csr = openssl_csr_new($dn, $privateKey, $options);
        if (! $csr instanceof \OpenSSLCertificateSigningRequest) {
            throw new RuntimeException('OpenSSL couldn’t create a certificate signing request.');
        }

        $x509 = openssl_csr_sign($csr, null, $privateKey, $this->daysExpiry, $options)
            ?: throw new RuntimeException('OpenSSL couldn’t sign the certificate.');

        if (! openssl_x509_export($x509, $certificate) || ! openssl_pkey_export($privateKey, $key)) {
            throw new RuntimeException('OpenSSL couldn’t export the key pair.');
        }

        $record = new KeyChainRecord([
            'class' => self::class,
            'settings' => $this->settings(),
            'description' => $this->description,
        ]);
        $record->setKeyPair($key, $certificate);

        return $record;
    }

    /**
     * @return array<string, string|null>
     */
    public function distinguishedName(): array
    {
        $dn = [];
        foreach (self::DN_FIELDS as $field) {
            $dn[$field] = $this->$field;
        }

        return $dn;
    }

    /**
     * The generation options, stored with the record. No key material.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        return [
            'digestAlgorithm' => $this->digestAlgorithm,
            'keyBits' => $this->keyBits,
            'daysExpiry' => $this->daysExpiry,
            'description' => $this->description,
            ...$this->distinguishedName(),
        ];
    }
}
