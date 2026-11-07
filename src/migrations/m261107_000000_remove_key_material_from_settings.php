<?php

namespace flipbox\keychain\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use flipbox\keychain\records\KeyChainRecord;

/**
 * Removes private keys and certificates that earlier versions copied, unencrypted,
 * into the `settings` column of key pairs created with "bring your own key".
 */
class m261107_000000_remove_key_material_from_settings extends Migration
{
    public const SENSITIVE_SETTINGS = ['key', 'certificate'];

    public function safeUp(): bool
    {
        $rows = (new Query())
            ->select(['id', 'settings'])
            ->from(KeyChainRecord::tableName())
            ->where(['not', ['settings' => null]])
            ->all($this->db);

        foreach ($rows as $row) {
            $settings = Json::decodeIfJson($row['settings']);
            if (!is_array($settings)) {
                continue;
            }

            $cleaned = array_diff_key($settings, array_flip(self::SENSITIVE_SETTINGS));
            if ($cleaned === $settings) {
                continue;
            }

            $this->update(
                KeyChainRecord::tableName(),
                ['settings' => Json::encode($cleaned)],
                ['id' => $row['id']],
                [],
                false
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        // The removed values are still in the `key` and `certificate` columns; nothing to restore.
        return true;
    }
}
