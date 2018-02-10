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

/**
 * Trait OpenSSL
 * @package flipbox\keychain\keypair\traits
 * @property string $startNoticeText
 * @property array $labels
 * @method getAttributes(): array
 * @method prompt($text, $options = []): string
 */
trait OpenSSLCliUtil
{

    /**
     * @return KeyPairInterface
     */
    protected function promptKeyPair()
    {
        $this->stdout(
            PHP_EOL . PHP_EOL . '****************************************************' . PHP_EOL .
            $this->startNoticeText . PHP_EOL .
            '****************************************************' . PHP_EOL . PHP_EOL
            , Console::FG_GREEN);

        $config = [];
        foreach ($this->getAttributes() as $attribute => $options) {
            $config[$attribute] = $this->prompt(
                $this->labels[$attribute],
                $options
            );
        }

        return new OpenSSLServiceModel($config);
    }

}