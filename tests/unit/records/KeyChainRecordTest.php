<?php

namespace flipbox\keychain\tests\unit\records;

use Codeception\Test\Unit;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

class KeyChainRecordTest extends Unit
{
    public function testByokDoesNotCopyKeyMaterialIntoSettings(): void
    {
        $plain = (new OpenSSL())->create();

        $record = (new Byok([
            'key' => $plain->key,
            'certificate' => $plain->certificate,
            'description' => 'BYOK',
        ]))->create();

        $settings = is_string($record->settings) ? json_decode($record->settings, true) : $record->settings;
        $this->assertSame(['description' => 'BYOK'], $settings);
    }

    public function testEncryptedByokRecordStoresNoPlaintextKey(): void
    {
        $plain = (new OpenSSL())->create();
        $record = (new Byok(['description' => 'BYOK']))->create();
        $record->setSubmittedKeyPair($plain->key, $plain->certificate);
        $record->isEncrypted = true;
        $record->pluginHandle = 'keychain';

        $this->assertTrue(KeyChain::getInstance()->getService()->save($record), json_encode($record->getErrors()));

        $row = (new \craft\db\Query())->from(KeyChainRecord::tableName())->where(['id' => $record->id])->one();
        foreach ($row as $column => $value) {
            $this->assertStringNotContainsString('PRIVATE KEY', (string)$value, "Plaintext key found in `$column`");
        }
    }

    public function testBlankSubmittedKeyKeepsStoredKey(): void
    {
        $plain = (new OpenSSL())->create();
        $id = $this->saveEncrypted($plain->key, $plain->certificate);

        $record = KeyChainRecord::findOne($id);
        $record->setSubmittedKeyPair('  ', $plain->certificate);
        $this->assertTrue(KeyChain::getInstance()->getService()->save($record), json_encode($record->getErrors()));

        $this->assertSame($plain->key, KeyChainRecord::findOne($id)->getDecryptedKey());
    }

    public function testSubmittedKeyReplacesStoredKey(): void
    {
        $old = (new OpenSSL())->create();
        $new = (new OpenSSL())->create();
        $id = $this->saveEncrypted($old->key, $old->certificate);

        $record = KeyChainRecord::findOne($id);
        $record->setSubmittedKeyPair($new->key, $new->certificate);
        $this->assertTrue(KeyChain::getInstance()->getService()->save($record), json_encode($record->getErrors()));

        $this->assertSame($new->key, KeyChainRecord::findOne($id)->getDecryptedKey());
    }

    public function testSubmittedKeyIsValidatedAgainstSubmittedCertificate(): void
    {
        $old = (new OpenSSL())->create();
        $other = (new OpenSSL())->create();
        $id = $this->saveEncrypted($old->key, $old->certificate);

        // A new key that doesn't match the certificate must fail, even though the stored pair is valid.
        $record = KeyChainRecord::findOne($id);
        $record->setSubmittedKeyPair($other->key, $old->certificate);

        $this->assertFalse($record->validate(['key']));
        $this->assertSame(['Key and certificate are not a pair.'], $record->getErrors('key'));
    }

    public function testBlankKeyOnNewRecordFailsValidation(): void
    {
        $plain = (new OpenSSL())->create();
        $record = (new Byok(['description' => 'BYOK']))->create();
        $record->setSubmittedKeyPair('', $plain->certificate);

        $this->assertFalse($record->validate(['key']));
        $this->assertNotEmpty($record->getErrors('key'));
    }

    private function saveEncrypted(string $key, string $certificate): int
    {
        $record = new KeyChainRecord([
            'class' => Byok::class,
            'pluginHandle' => 'keychain',
            'isEncrypted' => true,
        ]);
        $record->setSubmittedKeyPair($key, $certificate);
        $this->assertTrue(KeyChain::getInstance()->getService()->save($record), json_encode($record->getErrors()));

        return $record->id;
    }
}
