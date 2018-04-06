<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\keychain\keypair;


use craft\base\Model;
use flipbox\keychain\records\KeyChainRecord;

class Byok extends Model implements KeyPairInterface
{
    public $key;
    public $certificate;

    public $description;

    /**
     * @return KeyChainRecord
     */
    public function create(): KeyChainRecord
    {

        return new KeyChainRecord([
            'key' => $this->key,
            'certificate' => $this->certificate,
            'description' => $this->description,
            'settings' => $this->toArray(),
            'class' => self::class,
        ]);
    }
}