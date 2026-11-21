<?php

declare(strict_types=1);

use flipbox\keychain\keypair\Byok;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Runs the Craft 6 re-encryption migration, as an upgrade from keychain 5.x does.
 */
function reencryptKeyPairs(): void
{
    $migration = require dirname(__DIR__, 2).'/database/migrations/2026_11_20_000000_reencrypt_key_pairs_for_craft_6.php';
    $migration->silent()->up();
}

/**
 * Inserts a key pair the way keychain 5.x stored it.
 */
function insertCraft5Row(array $values = []): int
{
    $fixture = craft5Fixture();

    return DB::table(KeyChainRecord::TABLE)->insertGetId([
        'key' => $fixture['encryptedKey'],
        'certificate' => $fixture['encryptedCertificate'],
        'class' => Byok::class,
        'pluginHandle' => 'saml-sp',
        'isEncrypted' => true,
        'enabled' => true,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => (string) Str::uuid(),
        ...$values,
    ]);
}

/**
 * Sets (or with null, unsets) CRAFT_SECURITY_KEY, as an upgrading site's .env would.
 */
function setCraft5SecurityKey(?string $key): void
{
    if ($key === null) {
        putenv('CRAFT_SECURITY_KEY');
        unset($_ENV['CRAFT_SECURITY_KEY'], $_SERVER['CRAFT_SECURITY_KEY']);

        return;
    }

    putenv("CRAFT_SECURITY_KEY=$key");
    $_ENV['CRAFT_SECURITY_KEY'] = $_SERVER['CRAFT_SECURITY_KEY'] = $key;
}

beforeEach(fn () => setCraft5SecurityKey(craft5Fixture()['securityKey']));

afterEach(fn () => setCraft5SecurityKey(null));

it('re-encrypts Craft 5 key pairs with APP_KEY', function () {
    $fixture = craft5Fixture();
    $id = insertCraft5Row();

    reencryptKeyPairs();

    $record = KeyChainRecord::find($id);
    expect($record->getDecryptedKey())->toBe($fixture['key'])
        ->and($record->getDecryptedCertificate())->toBe($fixture['certificate'])
        ->and(Crypt::decryptString($record->key))->toBe($fixture['key']);
});

it('leaves unencrypted key pairs alone', function () {
    $id = insertCraft5Row(['isEncrypted' => false, 'key' => 'plain key', 'certificate' => 'plain certificate']);

    reencryptKeyPairs();

    expect(DB::table(KeyChainRecord::TABLE)->where('id', $id)->value('key'))->toBe('plain key');
});

it('can run again without touching re-encrypted rows', function () {
    $id = insertCraft5Row();
    reencryptKeyPairs();
    $stored = DB::table(KeyChainRecord::TABLE)->where('id', $id)->value('key');

    reencryptKeyPairs();

    expect(DB::table(KeyChainRecord::TABLE)->where('id', $id)->value('key'))->toBe($stored);
});

it('fails without the Craft 5 security key, changing nothing', function () {
    $id = insertCraft5Row();
    setCraft5SecurityKey(null);

    expect(fn () => reencryptKeyPairs())->toThrow(RuntimeException::class, 'CRAFT_SECURITY_KEY');

    expect(DB::table(KeyChainRecord::TABLE)->where('id', $id)->value('key'))->toBe(craft5Fixture()['encryptedKey']);
});

it('fails with the wrong security key, changing nothing', function () {
    $first = insertCraft5Row();
    $second = insertCraft5Row();
    setCraft5SecurityKey('a-different-security-key');

    expect(fn () => reencryptKeyPairs())->toThrow(RuntimeException::class, 'Couldn\'t decrypt the key of key pair '.$first);

    expect(DB::table(KeyChainRecord::TABLE)->whereIn('id', [$first, $second])->pluck('key')->unique()->all())
        ->toBe([craft5Fixture()['encryptedKey']]);
});

it('converts nothing if any row can’t be decrypted', function () {
    $good = insertCraft5Row();
    insertCraft5Row(['certificate' => base64_encode(random_bytes(128))]);

    expect(fn () => reencryptKeyPairs())->toThrow(RuntimeException::class);

    expect(DB::table(KeyChainRecord::TABLE)->where('id', $good)->value('key'))->toBe(craft5Fixture()['encryptedKey']);
});

it('doesn’t leak the security key or key material in its errors', function () {
    insertCraft5Row();
    setCraft5SecurityKey('secret-wrong-security-key');

    try {
        reencryptKeyPairs();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->not->toContain('secret-wrong-security-key')
            ->and($e->getMessage())->not->toContain(craft5Fixture()['encryptedKey']);

        return;
    }

    $this->fail('The migration succeeded with the wrong security key.');
});
