<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/7/18
 * Time: 11:16 PM
 */

namespace flipbox\keychain\controllers;

use flipbox\keychain\KeyChain;

class UpsertController extends AbstractUpsertController
{
    protected function getPlugin()
    {
        return KeyChain::getInstance();
    }
}
