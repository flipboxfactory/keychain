<?php

declare(strict_types=1);

namespace flipbox\keychain\models;

use CraftCms\Cms\Plugin\PluginSettings;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\services\KeyChainService;

class Settings extends PluginSettings
{
    /**
     * Whether new key pairs are encrypted at rest (with Laravel's `Crypt` and `APP_KEY`) unless asked otherwise.
     */
    public bool $encryptByDefault = true;

    /**
     * Defaults for generated OpenSSL key pairs.
     *
     * @see OpenSSL For the options.
     * @see KeyChainService::generateOpenssl() For the primary use.
     *
     * @var array<string, mixed>
     */
    public array $opensslDefaults = [
        'digestAlgorithm' => 'sha256',
        'keyBits' => 2048,
        'daysExpiry' => 365 * 2,
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
