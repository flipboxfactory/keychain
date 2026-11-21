<?php

declare(strict_types=1);

namespace flipbox\keychain\keypair;

use flipbox\keychain\records\KeyChainRecord;

interface KeyPairInterface
{
    /**
     * Returns a new, unsaved record holding the key pair.
     */
    public function create(): KeyChainRecord;
}
