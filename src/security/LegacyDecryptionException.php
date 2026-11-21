<?php

declare(strict_types=1);

namespace flipbox\keychain\security;

use RuntimeException;

/**
 * Thrown when a value encrypted by keychain 5.x can't be decrypted. Messages never include the value or the key.
 */
class LegacyDecryptionException extends RuntimeException {}
