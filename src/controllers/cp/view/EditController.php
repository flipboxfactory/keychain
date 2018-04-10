<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:13 PM
 */

namespace flipbox\keychain\controllers\cp\view;


use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

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