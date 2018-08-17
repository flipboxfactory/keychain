<?php

namespace flipbox\keychain\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * Whether or not to encrypt the key and cert by default.
     * This will use the Craft security key to encrypt the strings.
     *
     * @var bool
     */
    public $encryptByDefault = true;

    /**
     * Configuration the OpenSSL Model. This is used to autogenerate openssl key pairs.
     * @see \flipbox\keychain\keypair\OpenSSL For config.
     * @see \flipbox\keychain\services\KeyChainService::generateOpenssl() For primary use.
     * @var array
     */
    public $opensslDefaults = [
        'digestAlgorithm'=>'sha256',
        'keyBits'=>2048,
        'daysExpiry'=>365*2, //2 years
        'description' => 'My Key Pair',
        'countryName' => 'US',
        'stateOrProvinceName' => 'Colorado',
        'localityName' => 'Denver',
        'organizationName' => 'Flipbox Digital',
        'organizationalUnitName' => 'IT',
        'commonName' => 'flipboxdigital.com',
        'emailAddress' => 'keychain@flipboxdigital.com',
    ];
}
