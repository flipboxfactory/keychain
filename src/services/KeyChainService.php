<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/12/18
 * Time: 10:44 PM
 */

namespace flipbox\keychain\services;


use craft\base\Component;
use flipbox\keychain\keypair\KeyPairInterface;
use flipbox\keychain\records\KeyChainRecord;

class KeyChainService extends Component
{

    /**
     * @param KeyChainRecord $keyChainRecord
     * @return bool
     */
    public function save(KeyChainRecord $keyChainRecord)
    {
        return $keyChainRecord->save();
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
     * @param KeyChainRecord $record
     * @todo Support KMS
     */
    public function decrypt(KeyChainRecord $record)
    {

        if ($record->isEncrypted) {
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
     * @todo Support KMS
     */
    public function encrypt(KeyChainRecord $record)
    {

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