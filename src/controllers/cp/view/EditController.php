<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:13 PM
 */

namespace flipbox\keychain\controllers\cp\view;

use craft\base\Plugin;
use flipbox\keychain\KeyChain;

class EditController extends AbstractEditController
{
    /**
     * @return Plugin
     */
    protected function getPlugin()
    {
        return KeyChain::getInstance();
    }
}
