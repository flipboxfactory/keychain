<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 1/12/18
 * Time: 9:33 PM
 */

namespace flipbox\keychain\records;


use flipbox\ember\records\ActiveRecord;
use flipbox\ember\records\traits\StateAttribute;
use flipbox\keychain\KeyChain;

/**
 * Class KeyChainRecord
 * @package flipbox\keychain\records
 * @property string $certificate
 * @property string $key
 */
class KeyChainRecord extends ActiveRecord
{

    use StateAttribute;

    public $decryptedKey;
    public $decryptedCertificate;

    /**
     * @var bool
     * This is how we know if the key and cert has been decrypted
     */
    public $isDecrypted = false;

    /**
     * The table alias
     */
    const TABLE_ALIAS = 'keychain';


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(
            [
                ['key', 'isKeyCertAPair']
            ],
            parent::rules()
        );
    }

    /**
     * @param $attribute
     * @param $params
     * @param $validator
     */
    public function isKeyCertAPair($attribute, $params, $validator)
    {
        if (! openssl_x509_check_private_key($this->getDecryptedCertificate(), $this->getDecryptedKey())) {
            $this->addError($attribute, 'Key and certificate are not a pair.');
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {

        if ($this->isEncrypted) {
            KeyChain::getInstance()->getService()->encrypt($this);
        }
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * Call to fetch key using the getter decrypts
     * @return bool|string
     */
    public function getDecryptedKey()
    {
        if (! $this->decryptedKey) {
            KeyChain::getInstance()->getService()->decrypt($this);
            $this->isDecrypted = true;
        }

        return $this->decryptedKey;
    }

    /**
     * Call to fetch certificate using the getter decrypts
     * @return bool|string
     */
    public function getDecryptedCertificate()
    {
        if (! $this->decryptedCertificate) {
            KeyChain::getInstance()->getService()->decrypt($this);
        }

        return $this->decryptedCertificate;
    }

}