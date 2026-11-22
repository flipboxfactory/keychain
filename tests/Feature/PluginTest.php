<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\keychain\services\KeyChainService;
use Illuminate\Support\Facades\Schema;

it('installs the keychain table', function () {
    expect(Schema::hasTable(KeyChainRecord::TABLE))->toBeTrue()
        ->and(Schema::getColumnListing(KeyChainRecord::TABLE))->toEqualCanonicalizing([
            'id', 'description', 'key', 'certificate', 'class', 'pluginHandle', 'settings',
            'enabled', 'isEncrypted', 'dateCreated', 'dateUpdated', 'uid',
        ]);
});

it('marks the upgrade migrations as run on a fresh install', function () {
    $migrator = KeyChain::getInstance()->getMigrator();

    expect($migrator->getPendingMigrations())->toBe([]);
});

it('drops the table on uninstall', function () {
    KeyChain::getInstance()->uninstall();

    expect(Schema::hasTable(KeyChainRecord::TABLE))->toBeFalse();
});

it('resolves the service', function () {
    expect(KeyChain::getInstance()->getService())->toBeInstanceOf(KeyChainService::class)
        ->and(KeyChain::getInstance()->getService())->toBe(app(KeyChainService::class));
});
