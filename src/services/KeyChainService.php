<?php

declare(strict_types=1);

namespace flipbox\keychain\services;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use flipbox\keychain\keypair\KeyPairInterface;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\models\Settings;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;

#[Singleton]
class KeyChainService
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * Key pairs that belong to a plugin.
     *
     * @return Builder<KeyChainRecord>
     */
    public function findByPlugin(PluginInterface $plugin): Builder
    {
        return KeyChainRecord::query()->where('pluginHandle', $plugin->handle);
    }

    /**
     * Validates (unless `$runValidation` is false) and saves a key pair.
     */
    public function save(KeyChainRecord $record, bool $runValidation = true): bool
    {
        if ($runValidation && ! $record->validate()) {
            return false;
        }

        return $record->save();
    }

    public function delete(KeyChainRecord $record): bool
    {
        return (bool) $record->delete();
    }

    public function create(KeyPairInterface $keyPair): KeyChainRecord
    {
        return $keyPair->create();
    }

    /**
     * Generates and saves an OpenSSL key pair using the `opensslDefaults` setting.
     *
     * @param  array<string, mixed>  $attributes  Record attributes to set, e.g. `pluginHandle`. `isEncrypted`
     *                                            defaults to the `encryptByDefault` setting.
     */
    public function generateOpenssl(array $attributes = []): KeyChainRecord
    {
        $record = new OpenSSL($this->settings->opensslDefaults)->create();
        $record->isEncrypted = $this->settings->encryptByDefault;
        $record->fill(array_diff_key($attributes, array_flip(['key', 'certificate'])));

        $this->save($record);

        return $record;
    }
}
