<?php

namespace flipbox\keychain\tests\unit\migrations;

use Codeception\Test\Unit;
use craft\db\Query;
use craft\helpers\Db;
use craft\helpers\Json;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\migrations\m261107_000000_remove_key_material_from_settings;
use flipbox\keychain\records\KeyChainRecord;

class RemoveKeyMaterialFromSettingsTest extends Unit
{
    public function testRemovesKeyAndCertificateFromSettings(): void
    {
        $leaky = $this->insertRow(['key' => "-----BEGIN PRIVATE KEY-----\nabc", 'certificate' => 'cert', 'description' => 'BYOK']);
        $clean = $this->insertRow(['countryName' => 'US']);

        ob_start();
        (new m261107_000000_remove_key_material_from_settings())->safeUp();
        ob_end_clean();

        $this->assertSame(['description' => 'BYOK'], $this->settings($leaky));
        $this->assertSame(['countryName' => 'US'], $this->settings($clean));
    }

    private function insertRow(array $settings): int
    {
        Db::insert(KeyChainRecord::tableName(), [
            'key' => 'encrypted',
            'certificate' => 'encrypted',
            'class' => Byok::class,
            'pluginHandle' => 'keychain',
            'settings' => Json::encode($settings),
            'isEncrypted' => true,
        ]);

        return (int)\Craft::$app->getDb()->getLastInsertID();
    }

    private function settings(int $id): array
    {
        return Json::decode((new Query())->select('settings')->from(KeyChainRecord::tableName())->where(['id' => $id])->scalar());
    }
}
