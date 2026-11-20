<?php

declare(strict_types=1);

use flipbox\keychain\tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * The Craft 5 encrypted key pair in tests/fixtures (see its `_comment`).
 *
 * @return array{securityKey: string, key: string, certificate: string, encryptedKey: string, encryptedCertificate: string}
 */
function craft5Fixture(): array
{
    return json_decode((string) file_get_contents(__DIR__.'/fixtures/craft5-encrypted-key-pair.json'), true);
}
