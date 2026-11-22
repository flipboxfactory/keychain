<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers\cp\view;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use flipbox\keychain\KeyChain;

class EditController extends AbstractEditController
{
    protected function getPlugin(): PluginInterface
    {
        return KeyChain::getInstance();
    }
}
