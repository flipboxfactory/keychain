<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/6/18
 * Time: 8:18 PM
 */

namespace flipbox\keychain\traits;


use Craft;
use flipbox\keychain\events\AnyoneUsingMe;
use flipbox\keychain\KeyChain;
use yii\base\Event;
use yii\base\Module;

trait ModuleTrait
{
    /**
     *
     */
    protected function initKeyChain()
    {
        /**
         * Register on Craft
         */
        if (! Craft::$app->getModule(KeyChain::MODULE_ID)) {
            Craft::$app->setModule(KeyChain::MODULE_ID, [
                'class' => KeyChain::class
            ]);
        }


        /**
         * this will prevent another plugin from deleting the keychain table.
         */
        Event::on(
            KeyChain::class,
            KeyChain::EVENT_KEYCHAIN_IN_USE,
            function (AnyoneUsingMe $anyoneUsingMeEvent) {
                $anyoneUsingMeEvent->addModule($this);
            }
        );
    }

    /**
     * Modules
     */

    /**
     * @return KeyChain
     */
    public function getKeyChain()
    {
        return Craft::$app->getModule(KeyChain::MODULE_ID);
    }

}