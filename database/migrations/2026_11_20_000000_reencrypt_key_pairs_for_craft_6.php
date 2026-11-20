<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Migration;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\keychain\security\LegacyEncryption;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Re-encrypts key pairs from keychain 5.x for Craft 6.
 *
 * keychain 5.x encrypted key pairs with Craft 5's security key (`CRAFT_SECURITY_KEY`); 6.x uses Laravel's `Crypt`
 * (`APP_KEY`). Craft's upgrade guide removes `CRAFT_SECURITY_KEY`, so it has to stay set until this migration has
 * run. Without it, the migration fails rather than leaving key pairs nobody can decrypt; nothing is changed.
 *
 * Every row is decrypted and verified before any is written, and all rows are written in one transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table(KeyChainRecord::TABLE)
            ->where('isEncrypted', true)
            ->orderBy('id')
            ->get(['id', 'key', 'certificate']);

        $updates = [];
        foreach ($rows as $row) {
            // Already in the Craft 6 format, e.g. if this migration ran before
            if ($this->isCrypt($row->key) && $this->isCrypt($row->certificate)) {
                continue;
            }

            $updates[$row->id] = [
                'key' => Crypt::encryptString($this->decryptLegacy($row->key, $row->id, 'key')),
                'certificate' => Crypt::encryptString($this->decryptLegacy($row->certificate, $row->id, 'certificate')),
            ];
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $values) {
                DB::table(KeyChainRecord::TABLE)->where('id', $id)->update($values);
            }
        });

        if ($updates !== []) {
            $this->components->info(sprintf('Re-encrypted %d key pair(s). You can remove CRAFT_SECURITY_KEY once every plugin that needs it has been upgraded.', count($updates)));
        }
    }

    public function down(): void
    {
        // Rolling back would need the Craft 5 security key and Yii2's encryption, which Craft 6 doesn't have
        throw new RuntimeException('Key pairs can’t be re-encrypted for Craft 5. Restore a Craft 5 database backup instead.');
    }

    private function decryptLegacy(?string $value, int $id, string $column): string
    {
        $securityKey = app(GeneralConfig::class)->securityKey;

        if ($securityKey === '') {
            throw new RuntimeException(
                "Key pair $id is encrypted with your Craft 5 security key, which isn't set. Set CRAFT_SECURITY_KEY in your .env file to the security key from your Craft 5 site, run migrations again, and only then remove it."
            );
        }

        $raw = base64_decode((string) $value, true);
        if ($raw === false) {
            throw new RuntimeException("The $column of key pair $id isn't valid Craft 5 encrypted data.");
        }

        try {
            return LegacyEncryption::decrypt($raw, $securityKey);
        } catch (RuntimeException $e) {
            throw new RuntimeException("Couldn't decrypt the $column of key pair $id: {$e->getMessage()} Check that CRAFT_SECURITY_KEY is the security key from your Craft 5 site.", previous: $e);
        }
    }

    private function isCrypt(?string $value): bool
    {
        try {
            Crypt::decryptString((string) $value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
