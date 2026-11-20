# KeyChain

Key pair management for Craft CMS.

[![CI](https://github.com/flipboxfactory/keychain/actions/workflows/ci.yml/badge.svg)](https://github.com/flipboxfactory/keychain/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/flipboxfactory/keychain.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/keychain)
[![Total Downloads](https://img.shields.io/packagist/dt/flipboxfactory/keychain.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/keychain)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

KeyChain stores X.509 certificates and their private keys so other plugins can use them for signing and encryption. It is used by [SAML SP](https://github.com/flipboxfactory/saml-sp) and [SAML IdP](https://github.com/flipboxfactory/saml-idp).

- Generate self-signed OpenSSL key pairs from the control panel
- Bring your own key pair (BYOK)
- Encrypt keys at rest with the application key (`APP_KEY`)
- Validates that a key and certificate belong together

## Requirements

- Craft CMS 6 and PHP 8.5+ with the OpenSSL extension. For Craft 5, use KeyChain 5.x.

## Installation

```bash
composer require flipboxfactory/keychain
php artisan craft:plugin:install keychain
```

Admins manage key pairs at `/admin/keychain` in the control panel. Plugins that use KeyChain also link to their own key pair screens.

Private keys aren't shown when you edit a key pair. Leave the field blank to keep the current key, or paste a new one to replace it.

## Upgrading from Craft 5

1. On Craft 5, update to the latest KeyChain 5.x and run migrations.
2. Upgrade to Craft 6 following Craft's upgrade guide, and require `flipboxfactory/keychain:^6.0`.
3. **Keep `CRAFT_SECURITY_KEY` in your `.env` file until migrations have run.** Craft's guide says to remove it, but encrypted key pairs from Craft 5 can only be decrypted with it. KeyChain's migration re-encrypts them with `APP_KEY`, then you can remove it. Without it, the migration stops and changes nothing.

For developers: `findByPlugin()` returns an Eloquent query (`->get()` instead of `->all()`), `KeyChainRecord` is an Eloquent model with `validate()`/`errors()`, and the Yii console command base class and `GenerateKeyPair` asset bundle are gone.

## Usage

```php
use flipbox\keychain\KeyChain;

$service = KeyChain::getInstance()->getService();

// Generate and save a key pair, using the plugin's OpenSSL defaults
$keyPair = $service->generateOpenssl(['pluginHandle' => 'my-plugin']);

// Key pairs belonging to a plugin
$keyPairs = $service->findByPlugin($myPlugin)->get();

// PEM strings, decrypted if stored encrypted
$keyPair->getDecryptedKey();
$keyPair->getDecryptedCertificate();
```

## Settings

Override the defaults in `config/craft/keychain.php` (publish a starting point with `php artisan vendor:publish --tag=keychain`):

```php
return [
    'encryptByDefault' => true,
    'opensslDefaults' => [
        'digestAlgorithm' => 'sha256',
        'keyBits' => 2048,
        'daysExpiry' => 730,
        'commonName' => 'example.com',
        // countryName, stateOrProvinceName, localityName, organizationName,
        // organizationalUnitName, emailAddress, description
    ],
];
```

## Development

For a local Craft 6 site running this working copy, run `dev/up` (needs Docker; see [dev/README.md](dev/README.md)).


Tests use Pest with Orchestra Testbench and run on SQLite by default; set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` to use MySQL or PostgreSQL. Style is checked with Pint, static analysis with Larastan.

```bash
composer check      # check-cs + phpstan + test
composer fix-cs     # auto-fix code style
```

CI runs the same checks on SQLite, MySQL and PostgreSQL for every push and pull request.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE.md).
