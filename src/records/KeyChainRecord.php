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

/**
 * Class KeyChainRecord
 * @package flipbox\keychain\records
 * @property string $certificate
 * @property string $key
 */
class KeyChainRecord extends ActiveRecord
{

    use StateAttribute;

    /**
     * The table alias
     */
    const TABLE_ALIAS = 'keychain';

}