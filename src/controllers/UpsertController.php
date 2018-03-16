<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/7/18
 * Time: 11:16 PM
 */

namespace flipbox\keychain\controllers;

use Craft;
use craft\web\Controller;
use flipbox\keychain\controllers\cp\view\EditController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;

class UpsertController extends Controller
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
            $keypair = KeyChainRecord::find()->where([
                'id' => $id,
            ])->one();
        } else {
            $keypair = new KeyChainRecord();
        }

        /**
         * Set is decrypted so we know that the key and cert value is raw.
         */
        $keypair->isDecrypted = true;
        Craft::configure($keypair, [
            'key'         => $request->getBodyParam('key'),
            'certificate' => $request->getBodyParam('certificate'),
            'description' => $request->getBodyParam('description'),
            'enabled'     => $request->getBodyParam('enabled'),
            'isEncrypted' => $request->getBodyParam('isEncrypted'),
        ]);

        if (KeyChain::getInstance()->getService()->save($keypair)) {

            Craft::$app->getSession()->setNotice(Craft::t('keychain', 'Key pair saved.'));
        } else {

            Craft::$app->getSession()->setError(Craft::t('keychain', 'Key pair didn\'t save.'));
            return $this->redirect('/' . $request->getFullPath());
        }

        return $this->redirectToPostedUrl();
    }
}