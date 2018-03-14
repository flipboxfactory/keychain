<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/5/18
 * Time: 10:01 PM
 */

namespace flipbox\keychain;


use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use flipbox\keychain\events\AnyoneUsingMe;
use flipbox\keychain\services\KeyChainService;
use yii\base\Event;
use yii\base\Module;

class KeyChain extends Plugin
{

    public $hasCpSection = true;

    /**
     * Initializes the module.
     */
    public function init()
    {

        /**
         * Don't know why I have to do this but I do for craft cli to work.
         */
        Craft::setAlias('@flipbox/keychain/controllers', __DIR__ . '/controllers');
        parent::init();

        /**
         * Init Modules, Components, and Events
         */
        $this->initComponents();
        $this->initEvents();

    }

    public function initEvents()
    {
        // CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            [self::class, 'onRegisterCpUrlRules']
        );
    }

    /**
     * Init Continued
     */

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
     * @param RegisterUrlRulesEvent $event
     */
    public static function onRegisterCpUrlRules(RegisterUrlRulesEvent $event)
    {
        $event->rules = array_merge(
            $event->rules,
            [
                'keychain'                 => 'keychain/cp/view/general/index',
                'keychain/new'             => 'keychain/cp/view/edit/index',
                'keychain/<keypairId:\d+>' => 'keychain/cp/view/edit/index',
                'keychain/openssl'             => 'keychain/cp/view/edit/openssl',
            ]
        );
    }
}