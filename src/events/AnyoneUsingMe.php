<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/5/18
 * Time: 11:04 PM
 */

namespace flipbox\keychain\events;


use yii\base\Event;
use yii\base\Module;

class AnyoneUsingMe extends Event
{
    protected $modules = [];

    /**
     * @param Module $module
     */
    public function addModule(Module $module)
    {
        $this->modules[$module->getUniqueId()] = $module;
    }

    /**
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }
}