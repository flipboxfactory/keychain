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
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use yii\web\NotFoundHttpException;

abstract class AbstractEditController extends AbstractController
{
    use OpenSSL;

    const TEMPLATE_INDEX = 'keychain/_cp/edit';

    /**
     * @param array $variables
     * @return array
     */
    public static function getEditVariables(array $variables)
    {
        /** @var ?KeyChainRecord $keypair */
        $keypair = $variables['keypair'] ?: null;
        if (!$keypair) {
            return $variables;
        }

        if ($keypair->id) {
            $variables['title'] .= ': Edit';
            $variables['continueEditingUrl'] = $variables['baseCpPath'] . '/' . $keypair->id;

            $variables['formActions'] = [
                [
                    'label' => 'Save and continue editing',
                    'redirect' => \Craft::$app->getSecurity()->hashData($variables['continueEditingUrl'], null),
                    'shortcut' => true,
                ],
                [
                    'action' => 'keychain/upsert/change-status',
                    'label'  => $keypair->enabled ? 'Disable' : 'Enable',
                ],
                [
                    'action' => 'keychain/upsert/delete',
                    'label'  => 'Delete',
                    'destructive' => true,
                ],
            ];

            $crumb = [
                'url'   => UrlHelper::cpUrl($variables['continueEditingUrl']),
                'label' => $variables['keypair']->description ?: '(Untitled)',
            ];
        } else {
            $variables['title'] .= ': Create Bring Your Own Key';
            $crumb = [
                'url'   => UrlHelper::cpUrl($variables['baseCpPath'] . '/new'),
                'label' => 'New',
            ];
        }

        $variables['crumbs'][] = $crumb;

        return $variables;
    }

    /**
     * @param string|null $keypairId
     * @return \yii\web\Response
     */
    public function actionIndex($keypairId = null)
    {
        $variables = $this->getBaseVariables();

        if ($keypairId) {
            $variables['keypair'] = KeyChainRecord::find()->where([
                'id' => $keypairId,
            ])->one();
        } else {
            $variables['keypair'] = new KeyChainRecord();
        }

        $variables = self::getEditVariables($variables);
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

    /**
     * @param $keyId
     * @return \craft\web\Response|\yii\console\Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionDownloadCertificate($keyId)
    {
        $this->requireAdmin(false);

        /** @var KeyChainRecord $keychain */
        if (! $keychain = KeyChainRecord::find()->where([
            'id' => $keyId,
        ])->one()) {
            throw new NotFoundHttpException('Key not found');
        }

        return Craft::$app->response->sendContentAsFile($keychain->getDecryptedCertificate(), 'certificate.crt');
    }
}
