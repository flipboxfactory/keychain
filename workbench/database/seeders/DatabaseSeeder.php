<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\Plugins;
use CraftCms\Cms\Support\Facades\Sites;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Context;
use RuntimeException;

/**
 * Installs Craft and KeyChain for the sandbox, with a couple of key pairs to look at.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Cms::isInstalled()) {
            $status = $this->command->call('craft:install', [
                '--email' => config('workbench.admin_email'),
                '--username' => config('workbench.admin_username'),
                '--password' => config('workbench.admin_password'),
                '--siteName' => 'KeyChain Sandbox',
                '--siteUrl' => config('app.url'),
                '--language' => 'en-US',
                '--timezone' => 'UTC',
                '--no-interaction' => true,
            ]);

            if ($status !== 0) {
                throw new RuntimeException('Craft installation failed.');
            }

            Context::forgetHidden('craft.info');
            Context::forgetHidden('craft.isInstalled');
        }

        $site = Sites::getPrimarySite();

        if ($site->getBaseUrl(false) !== '$APP_URL') {
            $site->baseUrl = '$APP_URL';

            if (! Sites::saveSite($site)) {
                throw new RuntimeException('Failed to update the sandbox site URL.');
            }
        }

        if (! Plugins::isPluginInstalled('keychain')) {
            Plugins::installPlugin('keychain');
        }

        if (! Plugins::isPluginEnabled('keychain')) {
            Plugins::enablePlugin('keychain');
        }

        $this->seedKeyPairs();
    }

    private function seedKeyPairs(): void
    {
        if (KeyChainRecord::query()->exists()) {
            return;
        }

        $service = KeyChain::getInstance()->getService();

        $generated = new OpenSSL([
            'description' => 'Sandbox signing key (generated, encrypted)',
            'commonName' => 'sandbox.keychain.test',
        ])->create();
        $generated->fill(['pluginHandle' => 'keychain', 'isEncrypted' => true]);

        $other = new OpenSSL(['commonName' => 'byok.keychain.test'])->create();
        $brought = new Byok([
            'description' => 'Sandbox bring-your-own key (unencrypted)',
            'key' => $other->getDecryptedKey(),
            'certificate' => $other->getDecryptedCertificate(),
        ])->create();
        $brought->fill(['pluginHandle' => 'keychain', 'isEncrypted' => false]);

        foreach ([$generated, $brought] as $keyPair) {
            if (! $service->save($keyPair)) {
                throw new RuntimeException('Couldn’t seed a key pair: '.$keyPair->errors()->toJson());
            }
        }
    }
}
