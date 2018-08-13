<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\keychain\web\assets\bundles;


use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class GenerateKeyPair extends AssetBundle
{
    public $sourcePath = '@flipbox/keychain/web/assets';

    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->js = [
            'js/GenerateKeyPair.js',
        ];

        parent::init();
    }
}