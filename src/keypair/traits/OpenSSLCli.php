<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/6/18
 * Time: 9:48 PM
 */

namespace flipbox\keychain\keypair\traits;


use craft\helpers\Console;
use flipbox\keychain\keypair\KeyPairInterface;
use flipbox\keychain\keypair\OpenSSL as OpenSSLServiceModel;
use yii\console\ExitCode;

/**
 * Trait OpenSSL
 * @package flipbox\keychain\keypair\traits
 * @property string $startNoticeText
 * @property array $labels
 * @method getAttributes(): array
 * @method prompt($text, $options = []): string
 * @method promptKeyPair(): OpenSSLServiceModel
 */
trait OpenSSLCli
{

    abstract protected function getPlugin();

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

        //create key pair
        if (! $this->interactive) {

        } else {
            $keyPair = $this->promptKeyPair();
        }

        $keyPairRecord = $keyPair->create();

        if (! $this->getPlugin()->getKeyChain()->getService()->save($keyPairRecord)) {
            $this->stderr(
                sprintf('Failed to save new key pair to the database') . PHP_EOL
                , Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $this->stdout(
            sprintf(
                'Key pair save! Id: %s',
                $keyPairRecord->id
            ) . PHP_EOL
        );
        return ExitCode::OK;
    }
}