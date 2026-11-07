<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:02 PM
 */

namespace flipbox\keychain\controllers\cp\view;

use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;

class GeneralController extends AbstractController
{
    public const TEMPLATE_INDEX = 'keychain/_cp';

    protected function getPlugin()
    {
        return KeyChain::getInstance();
    }
}
