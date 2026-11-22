<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\models\Settings;
use flipbox\keychain\records\KeyChainRecord;

it('generates and saves a key pair for a plugin, encrypted by default', function () {
    $service = KeyChain::getInstance()->getService();

    $record = $service->generateOpenssl(['pluginHandle' => 'keychain']);

    expect($record->exists)->toBeTrue()
        ->and($record->isEncrypted)->toBeTrue();

    $found = $service->findByPlugin(KeyChain::getInstance())->whereKey($record->id)->first();
    expect($found)->toBeInstanceOf(KeyChainRecord::class)
        ->and(openssl_x509_check_private_key($found->getDecryptedCertificate(), $found->getDecryptedKey()))->toBeTrue();
});

it('follows the encryptByDefault setting', function () {
    app(Settings::class)->encryptByDefault = false;

    expect(KeyChain::getInstance()->getService()->generateOpenssl(['pluginHandle' => 'keychain'])->isEncrypted)->toBeFalse();
});

it('won’t take key material through generateOpenssl() attributes', function () {
    $other = new OpenSSL()->create();

    $record = KeyChain::getInstance()->getService()->generateOpenssl([
        'pluginHandle' => 'keychain',
        'key' => $other->getDecryptedKey(),
    ]);

    expect(KeyChainRecord::find($record->id)->getDecryptedKey())->not->toBe($other->getDecryptedKey());
});

it('only finds the plugin’s own key pairs', function () {
    $service = KeyChain::getInstance()->getService();
    $mine = $service->generateOpenssl(['pluginHandle' => 'keychain']);
    $service->generateOpenssl(['pluginHandle' => 'saml-sp']);

    expect($service->findByPlugin(KeyChain::getInstance())->pluck('id')->all())->toBe([$mine->id]);
});

it('doesn’t save an invalid key pair', function () {
    $record = new KeyChainRecord(['class' => OpenSSL::class, 'pluginHandle' => 'keychain']);
    $record->setKeyPair(new OpenSSL()->create()->getDecryptedKey(), new OpenSSL()->create()->getDecryptedCertificate());

    expect(KeyChain::getInstance()->getService()->save($record))->toBeFalse()
        ->and($record->exists)->toBeFalse();
});

it('deletes a key pair', function () {
    $service = KeyChain::getInstance()->getService();
    $record = $service->generateOpenssl(['pluginHandle' => 'keychain']);

    expect($service->delete($record))->toBeTrue()
        ->and(KeyChainRecord::find($record->id))->toBeNull();
});
