<?php

// KeyChain settings. Publish with `php artisan vendor:publish --tag=keychain`, which copies this file to
// `config/craft/keychain.php`.

return [
    // Encrypt new key pairs at rest with the application key (APP_KEY)
    'encryptByDefault' => true,

    // Defaults for generated OpenSSL key pairs
    // 'opensslDefaults' => [
    //     'digestAlgorithm' => 'sha256',
    //     'keyBits' => 2048,
    //     'daysExpiry' => 730,
    //     'description' => 'My Key Pair',
    //     'countryName' => 'US',
    //     'stateOrProvinceName' => 'Colorado',
    //     'localityName' => 'Denver',
    //     'organizationName' => 'Flipbox Digital',
    //     'organizationalUnitName' => 'IT',
    //     'commonName' => 'example.com',
    //     'emailAddress' => 'it@example.com',
    // ],
];
