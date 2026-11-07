# KeyChain

Key pair management for Craft CMS.

[![CI](https://github.com/flipboxfactory/keychain/actions/workflows/ci.yml/badge.svg)](https://github.com/flipboxfactory/keychain/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/flipboxfactory/keychain.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/keychain)
[![Total Downloads](https://img.shields.io/packagist/dt/flipboxfactory/keychain.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/keychain)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

KeyChain stores X.509 certificates and their private keys so other plugins can use them for signing and encryption. It is used by [SAML SP](https://github.com/flipboxfactory/saml-sp) and [SAML IdP](https://github.com/flipboxfactory/saml-idp).

- Generate self-signed OpenSSL key pairs from the control panel
- Bring your own key pair (BYOK)
- Encrypt keys at rest with Craft's security key
- Validates that a key and certificate belong together

## Requirements

- Craft CMS 5
- PHP 8.2+ with the OpenSSL extension

## Installation

```bash
composer require flipboxfactory/keychain
php craft plugin/install keychain
```

Key pairs are managed under **KeyChain** in the control panel.

## Usage

```php
use flipbox\keychain\KeyChain;

$service = KeyChain::getInstance()->getService();

// Generate and save a key pair, using the plugin's OpenSSL defaults
$keyPair = $service->generateOpenssl(['pluginHandle' => 'my-plugin']);

// Key pairs belonging to a plugin
$keyPairs = $service->findByPlugin($myPlugin)->all();

// PEM strings, decrypted if stored encrypted
$keyPair->getDecryptedKey();
$keyPair->getDecryptedCertificate();
```

## Settings

Override the defaults in `config/keychain.php`:

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

Checks follow Craft's [coding guidelines](https://craftcms.com/docs/5.x/extend/coding-guidelines.html): [craftcms/ecs](https://github.com/craftcms/ecs) and [craftcms/phpstan](https://github.com/craftcms/phpstan). Tests use Codeception with Craft's test module and need a MySQL database:

```bash
docker run -d --rm --name keychain-test-mysql -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=keychain_test -p 127.0.0.1:3306:3306 mysql:8.0
cp tests/.env.example tests/.env   # adjust CRAFT_DB_* if needed

composer check      # check-cs + phpstan + test
composer fix-cs     # auto-fix coding standards
```

CI runs the same checks on every push and pull request.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## License

MIT. See [LICENSE](LICENSE.md).
