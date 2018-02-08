<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/6/18
 * Time: 8:18 PM
 */

namespace flipbox\keychain\traits;


use craft\base\Plugin;
use flipbox\keychain\migrations\Install;
use yii\base\Module;

/**
 * Trait MigrateKeyChain
 * @package flipbox\keychain\traits
 * @method getDb
 */
trait MigrateKeyChain
{

    /**
     * @return Module
     */
    abstract protected function getModule(): Module;

    /**
     * @param Plugin $plugin
     * @return bool
     */
    protected function safeDownKeyChain()
    {
        $install = new Install([
            'db' => $this->getDb(),
        ]);
        return $install->externalSafeDown($this->getModule());
    }

    /**
     * @return mixed
     */
    protected function safeUpKeyChain()
    {
        $install = new Install([
            'db' => $this->getDb(),
        ]);

        return $install->safeUp();
    }
}