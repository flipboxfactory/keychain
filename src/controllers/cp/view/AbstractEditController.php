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
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

abstract class AbstractEditController extends AbstractController
{
    use OpenSSL;

    const TEMPLATE_INDEX = 'keychain/_cp/edit';

    /**
     * @param string|null $keypairId
     * @return \yii\web\Response
     */
    public function actionIndex($keypairId = null)
    {
        $variables = $this->getBaseVariables();

        if ($keypairId) {
            $keypair = $variables['keypair'] = KeyChainRecord::find()->where([
                'id' => $keypairId,
            ])->one();
            $variables['title'] .= ': Edit';


            $variables['actions'] = [
                [
                    //action list 1
                    [
                        'action' => 'keychain/upsert/change-status',
                        'label'  => $keypair->enabled ? 'Disable' : 'Enable',
                    ],
                    [
                        'action' => 'keychain/upsert/delete',
                        'label'  => 'Delete',
                    ],
                ],
            ];

            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $variables['baseCpPath'] . '/' . $keypairId
                ),
                'label' => $variables['keypair']->description,
            ];
        } else {
            $variables['keypair'] = new KeyChainRecord();
            $variables['title'] .= ': Create Bring Your Own Key';
            $crumb = [
                'url'   => UrlHelper::cpUrl(
                    $variables['baseCpPath'] . '/new'
                ),
                'label' => 'New',
            ];
        }

        $variables['crumbs'][] = $crumb;


        $variables = $this->beforeRender($variables);
        return $this->renderTemplate(
            static::TEMPLATE_INDEX,
            $variables
        );
    }

    /**
     * @return \yii\web\Response
     */
    public function actionOpenssl()
    {
        $variables = $this->getBaseVariables();

        $variables['options'] = [];
        $variables['options']['attributes'] = $this->getAttributes();
        $variables['options']['labels'] = $this->labels;

        $variables['keypair'] = new KeyChainRecord([
        ]);
        $variables['title'] .= ': Create OpenSSL Key Pair';
        $variables['crumbs'][] = [
            'url'   => UrlHelper::cpUrl(
                $this->getPlugin()->getUniqueId() . '/openssl'
            ),
            'label' => 'New',
        ];

        return $this->renderTemplate(
            static::TEMPLATE_INDEX . '/openssl',
            $variables
        );
    }
}
