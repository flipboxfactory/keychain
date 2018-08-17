<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:02 PM
 */

namespace flipbox\keychain\controllers\cp\view;

use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;

class GeneralController extends AbstractController
{

    const TEMPLATE_INDEX = 'keychain/_cp';

    protected function getPlugin()
    {
        return KeyChain::getInstance();
    }
}
