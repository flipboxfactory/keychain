<?php

declare(strict_types=1);

namespace flipbox\keychain\security;

use SensitiveParameter;

/**
 * Decrypts values that keychain 5.x (Craft 5) encrypted with `Craft::$app->getSecurity()->encryptByKey()`.
 *
 * Craft 6 encrypts with Laravel's `Crypt` (`APP_KEY`) and no longer ships Yii2's `Security` component, so this is a
 * decrypt-only port of `yii\base\Security::decryptByKey()` (yiisoft/yii2 2.0.x, BSD-3-Clause) with Craft 5's
 * defaults: AES-128-CBC, an HKDF-SHA256 key derived from the security key and a random salt, and a hex HMAC-SHA256
 * over the IV and ciphertext. Layout: salt (16 bytes) . hex HMAC (64 bytes) . IV (16 bytes) . ciphertext.
 *
 * Only the Craft 6 upgrade migration uses this, to re-encrypt stored key pairs with `Crypt`. It fails closed: any
 * malformed value or authentication failure throws instead of returning partial or unauthenticated data.
 */
final class LegacyEncryption
{
    private const string CIPHER = 'AES-128-CBC';

    private const int BLOCK_SIZE = 16;

    private const int KEY_SIZE = 16;

    private const string HASH = 'sha256';

    private const string AUTH_KEY_INFO = 'AuthorizationKey';

    private const int MAC_LENGTH = 64;

    /**
     * @param  string  $data  the raw (not base64-encoded) output of Craft 5's `encryptByKey()`
     *
     * @throws LegacyDecryptionException
     */
    public static function decrypt(string $data, #[SensitiveParameter] string $securityKey): string
    {
        if ($securityKey === '') {
            throw new LegacyDecryptionException('No Craft 5 security key is available to decrypt the value.');
        }

        if (strlen($data) < self::KEY_SIZE + self::MAC_LENGTH + self::BLOCK_SIZE + 1) {
            throw new LegacyDecryptionException('The value is too short to be Craft 5 encrypted data.');
        }

        $salt = substr($data, 0, self::KEY_SIZE);
        $key = hash_hkdf(self::HASH, $securityKey, self::KEY_SIZE, '', $salt);
        $authKey = hash_hkdf(self::HASH, $key, self::KEY_SIZE, self::AUTH_KEY_INFO);

        $mac = substr($data, self::KEY_SIZE, self::MAC_LENGTH);
        $payload = substr($data, self::KEY_SIZE + self::MAC_LENGTH);

        if (! hash_equals(hash_hmac(self::HASH, $payload, $authKey), $mac)) {
            throw new LegacyDecryptionException('The value failed authentication: it was altered, or encrypted with a different security key.');
        }

        $decrypted = openssl_decrypt(
            substr($payload, self::BLOCK_SIZE),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            substr($payload, 0, self::BLOCK_SIZE),
        );

        if ($decrypted === false) {
            throw new LegacyDecryptionException('OpenSSL couldn’t decrypt the value.');
        }

        return $decrypted;
    }
}
