<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/7/18
 * Time: 11:16 PM
 */

namespace flipbox\keychain\controllers;

use Craft;
use craft\base\Plugin;
use craft\web\Controller;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\controllers\cp\view\EditController;
use flipbox\keychain\KeyChain;
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
        $this->requirePostRequest();
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
            $keypair = new KeyChainRecord();
        }

        /**
         * Set is decrypted so we know that the key and cert value is raw.
         */
        $keypair->isDecrypted = true;
        Craft::configure($keypair, [
            'key'          => $request->getBodyParam('key'),
            'certificate'  => $request->getBodyParam('certificate'),
            'description'  => $request->getBodyParam('description'),
            'enabled'      => $request->getBodyParam('enabled'),
            'isEncrypted'  => $request->getBodyParam('isEncrypted'),
            'pluginHandle' => $request->getBodyParam('pluginHandle'),
        ]);

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
        $this->requirePostRequest();
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
}