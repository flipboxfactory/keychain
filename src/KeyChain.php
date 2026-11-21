<?php

declare(strict_types=1);

namespace flipbox\keychain;

use CraftCms\Cms\Plugin\Plugin;
use flipbox\keychain\models\Settings;
use flipbox\keychain\services\KeyChainService;

/**
 * Stores X.509 key pairs for other plugins.
 *
 * Control panel pages are in `routes/cp.php`, form actions in `routes/actions.php` (`actions/keychain/…`).
 *
 * @method Settings getSettings()
 */
class KeyChain extends Plugin
{
    public string $schemaVersion = '2.0.0';

    public function register(): void
    {
        parent::register();

        // Lets the service and controllers type-hint the plugin's settings
        $this->app->singleton(Settings::class, fn () => $this->getSettings());
    }

    public function getService(): KeyChainService
    {
        return $this->app->make(KeyChainService::class);
    }

    protected static function createSettings(): Settings
    {
        return new Settings;
    }
}
