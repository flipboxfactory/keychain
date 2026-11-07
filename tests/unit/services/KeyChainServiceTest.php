<?php

namespace flipbox\keychain\tests\unit\services;

use Codeception\Test\Unit;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\keychain\services\KeyChainService;

class KeyChainServiceTest extends Unit
{
    private KeyChainService $service;

    protected function _before(): void
    {
        $this->service = KeyChain::getInstance()->getService();
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $plain = (new OpenSSL())->create();
        $record = new KeyChainRecord([
            'key' => $plain->key,
            'certificate' => $plain->certificate,
            'isEncrypted' => true,
            'isDecrypted' => true,
        ]);

        $this->service->encrypt($record);

        $this->assertStringNotContainsString('PRIVATE KEY', $record->key);
        $this->assertStringNotContainsString('CERTIFICATE', $record->certificate);

        $stored = new KeyChainRecord([
            'key' => $record->key,
            'certificate' => $record->certificate,
            'isEncrypted' => true,
        ]);

        $this->assertSame($plain->key, $stored->getDecryptedKey());
        $this->assertSame($plain->certificate, $stored->getDecryptedCertificate());
    }

    public function testUnencryptedRecordsPassThrough(): void
    {
        $plain = (new OpenSSL())->create();
        $record = new KeyChainRecord([
            'key' => $plain->key,
            'certificate' => $plain->certificate,
            'isEncrypted' => false,
        ]);

        $this->assertSame($plain->key, $record->getDecryptedKey());
        $this->assertSame($plain->certificate, $record->getDecryptedCertificate());
    }

    public function testValidationRejectsMismatchedKeyAndCertificate(): void
    {
        $record = new KeyChainRecord([
            'key' => (new OpenSSL())->create()->key,
            'certificate' => (new OpenSSL())->create()->certificate,
            'isEncrypted' => false,
            'class' => OpenSSL::class,
            'pluginHandle' => 'keychain',
        ]);

        $this->assertFalse($record->validate(['key']));
        $this->assertSame(['Key and certificate are not a pair.'], $record->getErrors('key'));
    }

    public function testGenerateOpensslPersistsRecordForPlugin(): void
    {
        $record = $this->service->generateOpenssl(['pluginHandle' => 'keychain']);

        $this->assertNotNull($record->id);
        $found = $this->service->findByPlugin(KeyChain::getInstance())->andWhere(['id' => $record->id])->one();
        $this->assertInstanceOf(KeyChainRecord::class, $found);
        $this->assertTrue(openssl_x509_check_private_key($found->getDecryptedCertificate(), $found->getDecryptedKey()));
    }

    public function testEncryptedRecordIsStoredEncryptedAndReadsBack(): void
    {
        $plain = (new OpenSSL())->create();
        $record = new KeyChainRecord([
            'key' => $plain->key,
            'certificate' => $plain->certificate,
            'class' => OpenSSL::class,
            'pluginHandle' => 'keychain',
            'isEncrypted' => true,
            'isDecrypted' => true,
            'decryptedKey' => $plain->key,
            'decryptedCertificate' => $plain->certificate,
        ]);

        $this->assertTrue($this->service->save($record), json_encode($record->getErrors()));

        $row = (new \craft\db\Query())->from(KeyChainRecord::tableName())->where(['id' => $record->id])->one();
        $this->assertStringNotContainsString('PRIVATE KEY', $row['key']);

        $reloaded = KeyChainRecord::findOne($record->id);
        $this->assertSame($plain->key, $reloaded->getDecryptedKey());
    }
}
