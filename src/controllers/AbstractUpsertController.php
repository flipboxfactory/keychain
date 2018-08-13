<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/7/18
 * Time: 11:16 PM
 */

namespace flipbox\keychain\controllers;

use Craft;
use craft\web\Request;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\controllers\cp\view\EditController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

abstract class AbstractUpsertController extends AbstractController
{
    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionIndex()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        /** @var Request $request */
        $request = Craft::$app->request;
        $id = $request->getBodyParam('identifier');
        if ($id) {
            /** @var KeyChainRecord $keypair */
            $keypair = KeyChainRecord::find()->where([
                'id' => $id,
            ])->one();

            if ($keypair->isEncrypted) {
                $keypair->getDecryptedKey();
            }
        } else {
            $keypair = (new Byok([
                'key'         => $request->getBodyParam('key'),
                'certificate' => $request->getBodyParam('certificate'),
                'description' => $request->getBodyParam('description'),
            ]))->create();
        }

        /**
         * Set is decrypted so we know that the key and cert value is raw.
         */
        $keypair->isDecrypted = true;
        Craft::configure($keypair, [
            'key'          => $request->getBodyParam('key'),
            'certificate'  => $request->getBodyParam('certificate'),
            'description'  => $request->getBodyParam('description'),
            'isEncrypted'  => $request->getBodyParam('isEncrypted'),
            'pluginHandle' => $request->getBodyParam('pluginHandle'),
        ]);

        /**
         * Make sure enabled as a value
         */
        if ($keypair->enabled === null) {
            $keypair->enabled = true;
        }


        if (KeyChain::getInstance()->getService()->save($keypair)) {

            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair saved.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t save.'));
            return $this->renderTemplate(
                EditController::TEMPLATE_INDEX,
                array_merge(
                    $this->getBaseVariables(), [
                        'keypair' => $keypair,
                    ]
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionOpenssl()
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        /** @var Request $request */
        $request = Craft::$app->request;

        $keychainRecord = (new OpenSSL([
            'description'            => $request->getBodyParam('description'),
            'countryName'            => $request->getBodyParam('countryName'),
            'stateOrProvinceName'    => $request->getBodyParam('stateOrProvinceName'),
            'localityName'           => $request->getBodyParam('localityName'),
            'organizationName'       => $request->getBodyParam('organizationName'),
            'organizationalUnitName' => $request->getBodyParam('organizationalUnitName'),
            'commonName'             => $request->getBodyParam('commonName'),
            'emailAddress'           => $request->getBodyParam('emailAddress'),
        ]))->create();

        Craft::configure($keychainRecord, [
            'enabled'      => $request->getBodyParam('enabled') ?: false,
            'isEncrypted'  => $request->getBodyParam('isEncrypted') ?: false,
            'pluginHandle' => $request->getBodyParam('plugin'),
        ]);
        $keychainRecord->isDecrypted = true;

        if (KeyChain::getInstance()->getService()->save($keychainRecord)) {
            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair saved.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t save.'));
            return $this->renderTemplate(
                EditController::TEMPLATE_INDEX,
                array_merge(
                    $this->getBaseVariables(), [
                        'keypair' => $keychainRecord,
                    ]
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionGenerateOpenssl()
    {
        $this->requireAdmin();
        $this->requirePostRequest();
        $config = [];
        if ($plugin = Craft::$app->request->getParam('plugin')) {
            $config = [
                'pluginHandle' => $plugin,
            ];
        }

        /** @var KeyChainRecord $keyPair */
        $keyPair = KeyChain::getInstance()->getService()->generateOpenssl($config);

        if (Craft::$app->request->isAjax) {
            return $this->asJson($keyPair->toArray());
        }

        if (! $keyPair->hasErrors()) {
            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair saved.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t save.'));
            return $this->renderTemplate(
                EditController::TEMPLATE_INDEX,
                array_merge(
                    $this->getBaseVariables(), [
                        'keypair' => $keyPair,
                    ]
                )
            );
        }

        return $this->redirectToPostedUrl();
    }


    /**
     * @return \yii\web\Response
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionChangeStatus()
    {
        $this->requireAdmin();
        $keypairId = Craft::$app->request->getRequiredBodyParam('identifier');

        $keychainRecord = KeyChainRecord::find()->where([
            'id' => $keypairId,
        ])->one();

        $keychainRecord->enabled = ! $keychainRecord->enabled;

        if (KeyChain::getInstance()->getService()->save($keychainRecord)) {
            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair saved.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t save.'));
            return $this->renderTemplate(
                EditController::TEMPLATE_INDEX . '/openssl',
                array_merge(
                    $this->getBaseVariables(), [
                        'keypair' => $keychainRecord,
                    ]
                )
            );
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDelete()
    {
        $this->requireAdmin();
        $keypairId = Craft::$app->request->getRequiredBodyParam('identifier');

        $keychainRecord = KeyChainRecord::find()->where([
            'id' => $keypairId,
        ])->one();

        if (false !== KeyChain::getInstance()->getService()->delete($keychainRecord)) {
            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair deleted.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t delete.'));
            return $this->renderTemplate(
                EditController::TEMPLATE_INDEX,
                array_merge(
                    $this->getBaseVariables(), [
                        'keypair' => $keychainRecord,
                    ]
                )
            );
        }

        return $this->redirectToPostedUrl();
    }
}
