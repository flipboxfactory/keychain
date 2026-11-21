<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use flipbox\keychain\KeyChain;

class UpsertController extends AbstractUpsertController
{
    protected function getPlugin(): PluginInterface
    {
        return KeyChain::getInstance();
    }
}
