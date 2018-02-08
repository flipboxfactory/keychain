<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/5/18
 * Time: 10:01 PM
 */

namespace flipbox\keychain;


use Craft;
use flipbox\keychain\events\AnyoneUsingMe;
use flipbox\keychain\services\KeyChainService;
use yii\base\Module;

class KeyChain extends Module
{

    const MODULE_ID = 'keyChain';
    const EVENT_KEYCHAIN_IN_USE = 'onKeychainInUse';

    private $keychainModules = [];

    /**
     * Initializes the module.
     */
    public function init()
    {
        Craft::setAlias('@modules', __DIR__);
        parent::init();

        /**
         * Init Modules, Components, and Events
         */
        $this->initComponents();
        $this->initEvents();

    }

    /**
     * Init Continued
     */

    /**
     *
     */
    protected function initEvents()
    {
        $this->trigger(static::EVENT_KEYCHAIN_IN_USE, $event = new AnyoneUsingMe());
        $this->keychainModules = $event->getModules();
    }

    /**
     *
     */
    protected function initComponents()
    {
        return $this->setComponents([
            'keyChain' => KeyChainService::class,
        ]);
    }

    /**
     * COMPONENTS
     */

    /**
     * @return KeyChainService
     */
    public function getService()
    {
        return $this->get('keyChain');
    }


    /**
     * Utils
     */
    public function getVersion()
    {
        $composerFile = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
        $this->version = $composerFile['version'];
        return parent::getVersion(); // TODO: Change the autogenerated stub
    }

    /**
     * Plugin Registry
     * @see AnyoneUsingMe
     */

    /**
     * @param Module $module
     * @return $this
     */
    public function unsetKeyChainModule(Module $module)
    {
        if(isset($this->keychainModules[$module->getUniqueId()]))
        {
            unset($this->keychainModules[$module->getUniqueId()]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isKeyChainInUse()
    {
        return count($this->keychainModules) > 0;
    }
}