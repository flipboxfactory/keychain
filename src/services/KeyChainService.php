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

}