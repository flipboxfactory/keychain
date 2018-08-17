<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/1/18
 * Time: 10:01 PM
 */

namespace flipbox\keychain\keypair;

use flipbox\keychain\records\KeyChainRecord;

interface KeyPairInterface
{
    public function create() : KeyChainRecord;
}
