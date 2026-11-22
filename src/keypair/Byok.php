<?php

declare(strict_types=1);

namespace flipbox\keychain\keypair;

use CraftCms\Cms\Component\Component;
use flipbox\keychain\records\KeyChainRecord;

/**
 * "Bring your own key": wraps a key pair the user supplies.
 */
class Byok extends Component implements KeyPairInterface
{
    public ?string $key = null;

    public ?string $certificate = null;

    public ?string $description = null;

    public function create(): KeyChainRecord
    {
        $record = new KeyChainRecord([
            'description' => $this->description,
            // Never copy the key material into settings: that column isn't encrypted.
            'settings' => ['description' => $this->description],
            'class' => self::class,
        ]);

        if ($this->key !== null || $this->certificate !== null) {
            $record->setKeyPair((string) $this->key, (string) $this->certificate);
        }

        return $record;
    }
}
