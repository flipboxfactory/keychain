<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;

function saveKeyPair(string $key, string $certificate, bool $encrypted = true): KeyChainRecord
{
    $record = new KeyChainRecord(['class' => Byok::class, 'pluginHandle' => 'keychain', 'isEncrypted' => $encrypted]);
    $record->setSubmittedKeyPair($key, $certificate);

    expect(KeyChain::getInstance()->getService()->save($record))->toBeTrue($record->errors()->toJson());

    return $record;
}

function storedRow(KeyChainRecord $record): object
{
    return DB::table(KeyChainRecord::TABLE)->where('id', $record->id)->first();
}

it('stores an encrypted key pair without plaintext in any column', function () {
    $plain = new OpenSSL()->create();
    $record = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate());

    foreach ((array) storedRow($record) as $column => $value) {
        expect((string) $value)->not->toContain('PRIVATE KEY', "Plaintext key found in `$column`")
            ->and((string) $value)->not->toContain('BEGIN CERTIFICATE', "Plaintext certificate found in `$column`");
    }

    $reloaded = KeyChainRecord::find($record->id);
    expect($reloaded->getDecryptedKey())->toBe($plain->getDecryptedKey())
        ->and($reloaded->getDecryptedCertificate())->toBe($plain->getDecryptedCertificate());
});

it('stores an unencrypted key pair as is', function () {
    $plain = new OpenSSL()->create();
    $record = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate(), encrypted: false);

    expect(storedRow($record)->key)->toBe($plain->getDecryptedKey())
        ->and(KeyChainRecord::find($record->id)->getDecryptedKey())->toBe($plain->getDecryptedKey());
});

it('re-encodes the stored pair when encryption is switched', function (bool $from, bool $to) {
    $plain = new OpenSSL()->create();
    $record = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate(), encrypted: $from);

    $record = KeyChainRecord::find($record->id);
    $record->isEncrypted = $to;
    $record->save();

    expect(str_contains(storedRow($record)->key, 'PRIVATE KEY'))->toBe(! $to)
        ->and(KeyChainRecord::find($record->id)->getDecryptedKey())->toBe($plain->getDecryptedKey());
})->with([
    'encrypt' => [false, true],
    'decrypt' => [true, false],
]);

it('keeps the stored key when the submitted key is blank', function () {
    $plain = new OpenSSL()->create();
    $id = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate())->id;

    $record = KeyChainRecord::find($id);
    $record->setSubmittedKeyPair('  ', $plain->getDecryptedCertificate());

    expect(KeyChain::getInstance()->getService()->save($record))->toBeTrue()
        ->and(KeyChainRecord::find($id)->getDecryptedKey())->toBe($plain->getDecryptedKey());
});

it('replaces the stored key with a submitted one', function () {
    $old = new OpenSSL()->create();
    $new = new OpenSSL()->create();
    $id = saveKeyPair($old->getDecryptedKey(), $old->getDecryptedCertificate())->id;

    $record = KeyChainRecord::find($id);
    $record->setSubmittedKeyPair($new->getDecryptedKey(), $new->getDecryptedCertificate());

    expect(KeyChain::getInstance()->getService()->save($record))->toBeTrue()
        ->and(KeyChainRecord::find($id)->getDecryptedKey())->toBe($new->getDecryptedKey());
});

it('validates a submitted key against the submitted certificate', function () {
    $old = new OpenSSL()->create();
    $other = new OpenSSL()->create();
    $id = saveKeyPair($old->getDecryptedKey(), $old->getDecryptedCertificate())->id;

    // A new key that doesn't match the certificate must fail, even though the stored pair is valid
    $record = KeyChainRecord::find($id);
    $record->setSubmittedKeyPair($other->getDecryptedKey(), $old->getDecryptedCertificate());

    expect($record->validate())->toBeFalse()
        ->and($record->errors()->get('key'))->toBe(['Key and certificate are not a pair.'])
        ->and(KeyChain::getInstance()->getService()->save($record))->toBeFalse()
        ->and(KeyChainRecord::find($id)->getDecryptedKey())->toBe($old->getDecryptedKey());
});

it('requires a key for a new key pair', function () {
    $plain = new OpenSSL()->create();
    $record = new Byok(['description' => 'BYOK'])->create();
    $record->pluginHandle = 'keychain';
    $record->setSubmittedKeyPair('', $plain->getDecryptedCertificate());

    expect($record->validate())->toBeFalse()
        ->and($record->errors()->get('key'))->not->toBeEmpty();
});

it('rejects input that isn’t PEM', function () {
    $record = new Byok(['key' => 'not a key', 'certificate' => 'not a certificate'])->create();
    $record->pluginHandle = 'keychain';

    expect($record->validate())->toBeFalse()
        ->and($record->errors()->get('key'))->toBe(['Key and certificate are not a pair.']);
});

it('fails closed on a stored value it can’t decrypt', function () {
    $plain = new OpenSSL()->create();
    $record = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate());
    DB::table(KeyChainRecord::TABLE)->where('id', $record->id)->update(['key' => 'tampered']);

    KeyChainRecord::find($record->id)->getDecryptedKey();
})->throws(DecryptException::class);

it('never serializes the stored key or certificate', function () {
    $plain = new OpenSSL()->create();
    $record = saveKeyPair($plain->getDecryptedKey(), $plain->getDecryptedCertificate(), encrypted: false);

    expect(KeyChainRecord::find($record->id)->toArray())->not->toHaveKeys(['key', 'certificate']);
});
