<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/13/18
 * Time: 10:13 PM
 */

namespace flipbox\keychain\controllers\cp\view;


use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

class EditController extends Controller
{
    use OpenSSL;

    const TEMPLATE_INDEX = 'keychain/_cp/edit';

    public function actionIndex($keypairId = null)
    {
        $variables['title'] = Craft::t(KeyChain::getInstance()->getUniqueId(), KeyChain::getInstance()->name);

        if($keypairId) {
            $variables['keypair'] = KeyChainRecord::find()->where([
                'id' => $keypairId,
            ])->one();
            $variables['title'] .= ': Edit';
            $crumb = [
                'url' => UrlHelper::cpUrl(
                    KeyChain::getInstance()->getUniqueId() . '/' . $keypairId
                ),
                'label' => $variables['keypair']->description,
            ];
        }else{
            $variables['keypair'] = new KeyChainRecord([
            ]);
            $variables['title'] .= ': Create Bring Your Own Key';
            $crumb = [
                'url' => UrlHelper::cpUrl(
                    KeyChain::getInstance()->getUniqueId() . '/new'
                ),
                'label' => 'New',
            ];
        }

        $variables['crumbs'] = [
            [
                'url'=> UrlHelper::cpUrl('keychain'),
                'label' => Craft::t(KeyChain::getInstance()->getUniqueId(),KeyChain::getInstance()->name),
            ],
            $crumb,
        ];

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $this->getBaseCpPath();
        $variables['baseActionPath'] = $this->getBaseCpPath();
        $variables['baseCpPath'] = $this->getBaseCpPath();


        return $this->renderTemplate(
            static::TEMPLATE_INDEX,
            $variables
        );
    }

    public function actionOpenssl()
    {

        $variables['title'] = Craft::t(KeyChain::getInstance()->getUniqueId(), KeyChain::getInstance()->name);


        $variables['options'] = [];
        $variables['options']['attributes'] = $this->getAttributes();
        $variables['options']['labels'] = $this->labels;

        $variables['keypair'] = new KeyChainRecord([
        ]);
        $variables['title'] .= ': Create OpenSSL Key Pair';
        $crumb = [
            'url' => UrlHelper::cpUrl(
                KeyChain::getInstance()->getUniqueId() . '/openssl'
            ),
            'label' => 'New',
        ];

        $variables['crumbs'] = [
            [
                'url'=> UrlHelper::cpUrl('keychain'),
                'label' => Craft::t(KeyChain::getInstance()->getUniqueId(),KeyChain::getInstance()->name),
            ],
            $crumb,
        ];

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $this->getBaseCpPath();
        $variables['baseActionPath'] = $this->getBaseCpPath();
        $variables['baseCpPath'] = $this->getBaseCpPath();


        return $this->renderTemplate(
            static::TEMPLATE_INDEX .'/openssl',
            $variables
        );
    }

    /**
     * @return string
     */
    protected function getBaseCpPath(): string
    {
        return KeyChain::getInstance()->getUniqueId();
    }

}