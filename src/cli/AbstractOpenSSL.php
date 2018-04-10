<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */


namespace flipbox\keychain\cli;


use craft\base\Plugin;
use craft\helpers\Console;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;
use yii\console\Controller;
use yii\console\ExitCode;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\keypair\traits\OpenSSLCliUtil;

/**
 * Class AbstractOpenSSL
 * @package flipbox\keychain\keypair\traits
 */
abstract class AbstractOpenSSL extends Controller
{

    use OpenSSL, OpenSSLCliUtil;

    /**
     * @return Plugin
     */
    abstract protected function getPlugin();

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(
            array_keys($this->getAttributes()),
            [
                'force',
            ],
            parent::options($actionID)
        );
    }

    /**
     * @inheritdoc
     */
    public function optionAliases()
    {
        return array_merge(
            [
                'f' => 'force',
            ],
            parent::optionAliases()
        );
    }

    /**
     * @return int
     */
    public function actionCreateKeyPair()
    {

        /** @var \flipbox\keychain\keypair\OpenSSL $keyPair */
        $keyPair = $this->promptKeyPair();

        /** @var KeyChainRecord $keyPairRecord */
        $keyPairRecord = $keyPair->create();


        $keyPairRecord->pluginHandle = $this->getPlugin()->getUniqueId();
        $keyPairRecord->isEncrypted = true;
        $keyPairRecord->isDecrypted = true;


        if (! KeyChain::getInstance()->getService()->save($keyPairRecord)) {
            $this->stderr(
                sprintf('Failed to save new key pair to the database') . PHP_EOL
                , Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout(
            sprintf(
                'Key pair save! Id: %s',
                $keyPairRecord->id
            ) . PHP_EOL, Console::FG_GREEN
        );
        return ExitCode::OK;
    }
}