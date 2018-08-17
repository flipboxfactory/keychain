<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/7/18
 * Time: 11:16 PM
 */

namespace flipbox\keychain\controllers;

use Craft;
use craft\base\Plugin;
use craft\web\Controller;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\controllers\cp\view\EditController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;

class UpsertController extends AbstractUpsertController
{
    protected function getPlugin()
    {
        return KeyChain::getInstance();
    }
}
