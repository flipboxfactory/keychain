<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers\cp;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Support\Url;
use flipbox\keychain\KeyChain;

/**
 * Base for keychain's control panel controllers.
 *
 * Other plugins can show keychain's screens in their own section (e.g. `saml-sp/keychain`) by extending the view
 * controllers, returning themselves from {@see getPlugin()} and routing to them from their `routes/cp.php`.
 */
abstract class AbstractController
{
    use RespondsWithFlash;

    /**
     * The plugin whose key pairs are managed.
     */
    abstract protected function getPlugin(): PluginInterface;

    protected function isKeyChain(): bool
    {
        return $this->getPlugin() instanceof KeyChain;
    }

    /**
     * The CP path of the key pair screens, e.g. `keychain` or `saml-sp/keychain`.
     */
    protected function getBaseCpPath(): string
    {
        $handle = KeyChain::getInstance()->handle;

        return $this->isKeyChain() ? $handle : $this->getPlugin()->handle.'/'.$handle;
    }

    /**
     * The path that keychain's form actions live under (`actions/<this>/upsert/…`).
     */
    protected function getBaseActionPath(): string
    {
        return KeyChain::getInstance()->handle;
    }

    protected function actionUrl(string $action): string
    {
        return Url::actionUrl($this->getBaseActionPath().'/'.$action);
    }

    protected function title(?string $suffix = null): string
    {
        $title = ($this->isKeyChain() ? '' : $this->getPlugin()->name.': ').KeyChain::getInstance()->name;

        return $suffix === null ? (string) $title : "$title: $suffix";
    }

    /**
     * @return list<ActionItem>
     */
    protected function crumbs(): array
    {
        $crumbs = [];

        if (! $this->isKeyChain()) {
            $crumbs[] = new ActionItem()->label((string) $this->getPlugin()->name)->href(Url::cpUrl($this->getPlugin()->handle));
        }

        $crumbs[] = new ActionItem()->label('KeyChain')->href(Url::cpUrl($this->getBaseCpPath()));

        return $crumbs;
    }
}
