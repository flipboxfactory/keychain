<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/12/18
 * Time: 10:44 PM
 */

namespace flipbox\keychain\services;


use craft\base\Component;
use craft\base\Plugin;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\KeyPairInterface;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use yii\db\ActiveQuery;

class KeyChainService extends Component
{

    /**
     * @param Plugin $plugin
     * @return ActiveQuery
     */
    public function findByPlugin(Plugin $plugin)
    {
        return KeyChainRecord::find()->where([
            'pluginHandle' => $plugin->handle,
        ]);
    }

    /**
     * @param KeyChainRecord $keyChainRecord
     * @return bool
     */
    public function save(KeyChainRecord $keyChainRecord, $runValidation = true, $attributeNames = null)
    {
        if (! $runValidation && $keyChainRecord->validate()) {
            return false;
        }

        return $keyChainRecord->save();
    }

    /**
     * @param KeyChainRecord $keyChainRecord
     * @return false|int
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function delete(KeyChainRecord $keyChainRecord)
    {
        return $keyChainRecord->delete();
    }

    /**
     * @param KeyPairInterface $keyPairConfig
     * @return KeyChainRecord
     */
    public function create(KeyPairInterface $keyPairConfig)
    {
        return $keyPairConfig->create();
    }

    /**
     * @param array $config
     * @return KeyChainRecord
     */
    public function generateOpenssl($config = [])
    {
        /**
         * Create the key pair using the defaults
         */
        $openssl = new OpenSSL(
            KeyChain::getInstance()->getSettings()->opensslDefaults
        );
        $keyPair = $openssl->create();

        /**
         * default this to false.
         */
        $keyPair->isEncrypted = false;

        /**
         * Merge in any configs passed
         */
        \Craft::configure($keyPair, $config);

        /**
         * Save
         */
        $this->save($keyPair);

        return $keyPair;
    }

    /**
     * @param KeyChainRecord $record
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function decrypt(KeyChainRecord $record)
    {

        if ($record->isEncrypted && ! $record->isDecrypted) {
            $record->decryptedKey = \Craft::$app->getSecurity()->decryptByKey(
                base64_decode($record->key),
                \Craft::$app->getConfig()->getGeneral()->securityKey
            );
            $record->decryptedCertificate = \Craft::$app->getSecurity()->decryptByKey(
                base64_decode($record->certificate),
                \Craft::$app->getConfig()->getGeneral()->securityKey
            );
        } else {
            $record->decryptedKey = $record->key;
            $record->decryptedCertificate = $record->certificate;
        }

    }

    /**
     * @param KeyChainRecord $record
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function encrypt(KeyChainRecord $record)
    {

        if ($record->isEncrypted && $record->isDecrypted) {
            /**
             * Encrypt data at rest
             */
            $record->key = base64_encode(\Craft::$app->getSecurity()->encryptByKey(
                $record->key,
                \Craft::$app->getConfig()->getGeneral()->securityKey
            ));
            $record->certificate = base64_encode(\Craft::$app->getSecurity()->encryptByKey(
                $record->certificate,
                \Craft::$app->getConfig()->getGeneral()->securityKey
            ));

        }
    }
}