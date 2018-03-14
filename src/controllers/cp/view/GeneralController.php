<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:02 PM
 */

namespace flipbox\keychain\controllers\cp\view;


use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;

class GeneralController extends Controller
{

    const TEMPLATE_INDEX = 'keychain/_cp';

    public function actionIndex()
    {
        $variables['crumbs'] = [
            [
                'url'=> UrlHelper::cpUrl('keychain'),
                'label' => 'KeyChain'
            ],
        ];

        $variables['keypairs'] = KeyChainRecord::find()->all();
        $variables['title'] = Craft::t(KeyChain::getInstance()->getUniqueId(), KeyChain::getInstance()->name);
        return $this->renderTemplate(
            static::TEMPLATE_INDEX,
            $variables
        );
    }

}