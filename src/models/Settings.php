<?php

namespace flipbox\keychain\models;


use craft\base\Model;

class Settings extends Model
{
    /**
     * Whether or not to encrypt the key and cert by default.
     * This will use the Craft security key to encrypt the strings.
     *
     * @var bool
     */
    public $encryptByDefault = true;

}