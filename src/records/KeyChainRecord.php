<?php

declare(strict_types=1);

namespace flipbox\keychain\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\MessageBag;
use SensitiveParameter;

use function CraftCms\Cms\t;

/**
 * A stored key pair.
 *
 * Plaintext key material is only held in memory: {@see setKeyPair()} (or {@see setSubmittedKeyPair()}) sets it, and
 * saving writes the `key` and `certificate` columns from it, encrypted with Laravel's `Crypt` (`APP_KEY`) when
 * `isEncrypted` is set. Read it back with {@see getDecryptedKey()} and {@see getDecryptedCertificate()}.
 *
 * @property int $id
 * @property string|null $description
 * @property string $key The stored private key: ciphertext when `isEncrypted`. Use {@see getDecryptedKey()}.
 * @property string $certificate The stored certificate: ciphertext when `isEncrypted`. Use {@see getDecryptedCertificate()}.
 * @property string $class
 * @property string $pluginHandle
 * @property array<string, mixed>|null $settings Never contains key material: this column isn't encrypted.
 * @property bool $enabled
 * @property bool $isEncrypted
 * @property string $uid
 */
class KeyChainRecord extends BaseModel
{
    use EmberTrait;
    use HasUid;

    public const string TABLE = 'keychain';

    /**
     * @deprecated 6.0.0 use {@see TABLE}.
     */
    public const string TABLE_ALIAS = self::TABLE;

    protected $table = self::TABLE;

    protected $attributes = [
        'enabled' => true,
        'isEncrypted' => true,
    ];

    protected $hidden = ['key', 'certificate'];

    private ?string $decryptedKey = null;

    private ?string $decryptedCertificate = null;

    /**
     * Whether the in-memory key pair still has to be written to the `key` and `certificate` columns.
     */
    private bool $keyPairChanged = false;

    private ?MessageBag $errors = null;

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            if ($record->keyPairChanged || $record->isDirty('isEncrypted')) {
                $record->writeKeyPair();
            }
        });
    }

    /**
     * The table name, for callers that build queries or foreign keys against it.
     */
    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'isEncrypted' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Sets the plaintext key pair. Saving stores it, encrypted when `isEncrypted` is set.
     */
    public function setKeyPair(#[SensitiveParameter] string $key, string $certificate): void
    {
        $this->decryptedKey = $key;
        $this->decryptedCertificate = $certificate;
        $this->keyPairChanged = true;
    }

    /**
     * Sets the key pair from submitted (plaintext) values.
     *
     * A blank key keeps the stored key, so the control panel never has to send the private key to the browser just
     * to save other changes. Validation then checks the pair that will actually be saved.
     */
    public function setSubmittedKeyPair(#[SensitiveParameter] ?string $key, ?string $certificate): void
    {
        if ($key === null || trim($key) === '') {
            $key = $this->exists ? $this->getDecryptedKey() : '';
        }

        $this->setKeyPair($key, (string) $certificate);
    }

    /**
     * The plaintext private key.
     */
    public function getDecryptedKey(): string
    {
        return $this->decryptedKey ??= $this->decryptColumn('key');
    }

    /**
     * The plaintext certificate.
     */
    public function getDecryptedCertificate(): string
    {
        return $this->decryptedCertificate ??= $this->decryptColumn('certificate');
    }

    /**
     * Validates the record, collecting messages in {@see errors()}.
     */
    public function validate(): bool
    {
        $this->errors = new MessageBag;

        foreach (['class', 'pluginHandle'] as $attribute) {
            if (blank($this->getAttribute($attribute))) {
                $this->errors->add($attribute, t('{attribute} cannot be blank.', ['attribute' => $attribute], 'keychain'));
            }
        }

        $key = $this->getDecryptedKey();
        $certificate = $this->getDecryptedCertificate();

        if (trim($key) === '') {
            $this->errors->add('key', t('Private Key cannot be blank.', category: 'keychain'));
        }

        if (trim($certificate) === '') {
            $this->errors->add('certificate', t('Certificate cannot be blank.', category: 'keychain'));
        }

        if (! $this->errors->has(['key', 'certificate']) && ! self::isPair($key, $certificate)) {
            $this->errors->add('key', t('Key and certificate are not a pair.', category: 'keychain'));
        }

        return $this->errors->isEmpty();
    }

    public function errors(): MessageBag
    {
        return $this->errors ??= new MessageBag;
    }

    private static function isPair(#[SensitiveParameter] string $key, string $certificate): bool
    {
        // OpenSSL warns (rather than returning false) on input it can't parse
        return @openssl_x509_check_private_key($certificate, $key) === true;
    }

    /**
     * Writes the in-memory key pair to the `key` and `certificate` columns.
     */
    private function writeKeyPair(): void
    {
        $key = $this->getDecryptedKey();
        $certificate = $this->getDecryptedCertificate();

        $this->setAttribute('key', $this->isEncrypted ? Crypt::encryptString($key) : $key);
        $this->setAttribute('certificate', $this->isEncrypted ? Crypt::encryptString($certificate) : $certificate);
        $this->keyPairChanged = false;
    }

    private function decryptColumn(string $column): string
    {
        // When `isEncrypted` changed in memory, the stored value is still in the original format
        $encrypted = $this->isDirty('isEncrypted') ? (bool) $this->getOriginal('isEncrypted') : $this->isEncrypted;
        $value = (string) ($this->isDirty($column) ? $this->getOriginal($column) : $this->getAttribute($column));

        if ($value === '' || ! $encrypted) {
            return $value;
        }

        // Throws (fails closed) on anything Crypt didn't produce with this APP_KEY
        return Crypt::decryptString($value);
    }
}
