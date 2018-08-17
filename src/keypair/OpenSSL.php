<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/1/18
 * Time: 9:52 PM
 */

namespace flipbox\keychain\keypair;

use craft\base\Model;
use flipbox\keychain\records\KeyChainRecord;

class OpenSSL extends Model implements KeyPairInterface
{
    public $digestAlgorithm = 'sha256';
    public $keyBits = 2048;

    public $daysExpiry = 365;

    public $description = 'What is this key for?';

    public $countryName;
    public $stateOrProvinceName;
    public $localityName;
    public $organizationName;
    public $organizationalUnitName;
    public $commonName;
    public $emailAddress;

    public function attributes()
    {
        return [
            'countryName',
            'stateOrProvinceName',
            'localityName',
            'organizationName',
            'organizationalUnitName',
            'commonName',
            'emailAddress',
        ];
    }

    public function create(): KeyChainRecord
    {

        // Generate a new private (and public) key pair
        $privkey = openssl_pkey_new([
            "private_key_bits" => $this->keyBits,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        // Generate a certificate signing request
        $csr = openssl_csr_new($this->toArray($this->attributes()), $privkey, ['digest_alg' => $this->digestAlgorithm]);

        // Generate a self-signed cert, valid for 365 days
        $x509 = openssl_csr_sign($csr, null, $privkey, $this->daysExpiry, ['digest_alg' => $this->digestAlgorithm]);

        // Save your private key, CSR and self-signed cert for later use
        openssl_csr_export($csr, $csrout);
        openssl_x509_export($x509, $certout);
        openssl_pkey_export($privkey, $pkeyout);

        return new KeyChainRecord([
            'certificate' => $certout,
            'key'         => $pkeyout,
            'class'       => self::class,
            'settings'    => $this->toArray(),
            'description' => $this->description,
        ]);
    }
}
