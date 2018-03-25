<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 3/18/18
 * Time: 9:45 PM
 */

namespace flipbox\keychain\controllers\cp;


use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\base\Plugin;
use flipbox\keychain\KeyChain;

abstract class AbstractController extends Controller
{
    /**
     * @return Plugin
     */
    abstract protected function getPlugin(): Plugin;

    /**
     * @return array
     */
    protected function getBaseVariables()
    {
        $variables = [
            'pluginHandle'       => $this->getPlugin()->handle,
            'title'              => $this->getPlugin()->name . ' - ' . KeyChain::getInstance()->name,
            // Set the "Continue Editing" URL
            'continueEditingUrl' => $this->getBaseCpPath(),
            'baseActionPath'     => $this->getBaseActionPath(),
            'baseCpPath'         => $this->getBaseCpPath(),
        ];
        $crumbPath = '';
        if (! ($this->getPlugin() instanceof KeyChain)) {
            $variables['crumbs'][] = [
                'url'   => UrlHelper::cpUrl($this->getPlugin()->getUniqueId()),
                'label' => $this->getPlugin()->name,
            ];
        }
        $variables['crumbs'][] = [
            'url'   => UrlHelper::cpUrl($variables['baseCpPath']),
            'label' => 'KeyChain',
        ];
        return $variables;
    }

    /**
     * @return string
     */
    protected function getBaseActionPath(): string
    {
        return KeyChain::getInstance()->getUniqueId();
    }

    /**
     * @return string
     */
    protected function getBaseCpPath(): string
    {
        if ($this->getPlugin() instanceof KeyChain) {
            return KeyChain::getInstance()->getUniqueId();
        }
        return $this->getPlugin()->getUniqueId() . '/' . KeyChain::getInstance()->getUniqueId();
    }

}