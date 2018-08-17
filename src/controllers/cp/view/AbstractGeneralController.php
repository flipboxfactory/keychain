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

abstract class AbstractGeneralController extends AbstractController
{

    const TEMPLATE_INDEX = 'keychain/_cp';

    public function actionIndex()
    {
        $variables = $this->getBaseVariables();

        $variables['keypairs'] = KeyChain::getInstance()->getService()->findByPlugin(
            $this->getPlugin()
        )->all();

        $variables['title'] = Craft::t(
            $this->getPlugin()->getUniqueId(),
            ($this->getPlugin() instanceof KeyChain ? '' : $this->getPlugin()->name . ': ') .
            KeyChain::getInstance()->name
        );

        $variables = $this->beforeRender($variables);
        return $this->renderTemplate(
            static::TEMPLATE_INDEX,
            $variables
        );
    }
}
