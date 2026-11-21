<?php

declare(strict_types=1);

use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;

const CP_SCREENS = ['admin/keychain', 'admin/keychain/new', 'admin/keychain/openssl'];

const ACTIONS = ['upsert', 'upsert/openssl', 'upsert/generate-openssl', 'upsert/change-status', 'upsert/delete'];

function savedKeyPair(bool $encrypted = true): KeyChainRecord
{
    $plain = new OpenSSL(['description' => 'Signing'])->create();
    $plain->fill(['pluginHandle' => 'keychain', 'isEncrypted' => $encrypted]);
    expect(KeyChain::getInstance()->getService()->save($plain))->toBeTrue();

    return $plain;
}

/**
 * A line from the middle of a PEM block, which a page would contain if it rendered the block.
 */
function pemLine(string $pem): string
{
    return explode("\n", trim($pem))[2];
}

describe('screens', function () {
    it('requires a signed-in user', function (string $uri) {
        $this->get($uri)->assertRedirect();
    })->with(CP_SCREENS);

    it('requires an admin', function (string $uri) {
        $this->actingAs($this->cpUser(), 'craft')->get($uri)->assertForbidden();
    })->with(CP_SCREENS);

    it('renders for admins', function (string $uri) {
        $this->actingAs($this->admin(), 'craft')->get($uri)->assertOk();
    })->with(CP_SCREENS);

    it('lists key pairs', function () {
        $keyPair = savedKeyPair();

        $this->actingAs($this->admin(), 'craft')->get('admin/keychain')
            ->assertOk()
            ->assertSee('Signing')
            // Inertia JSON-encodes the page data, escaping slashes
            ->assertSee("\\/admin\\/keychain\\/$keyPair->id", false);
    });

    it('shows the certificate but never the private key', function (bool $encrypted) {
        $keyPair = savedKeyPair($encrypted);

        $this->actingAs($this->admin(), 'craft')->get("admin/keychain/$keyPair->id")
            ->assertOk()
            ->assertSee(pemLine($keyPair->getDecryptedCertificate()), false)
            ->assertDontSee(pemLine($keyPair->getDecryptedKey()), false)
            ->assertDontSee('PRIVATE KEY-----\n', false);
    })->with(['encrypted' => true, 'unencrypted' => false]);

    it('404s for a missing key pair', function () {
        $this->actingAs($this->admin(), 'craft')->get('admin/keychain/999')->assertNotFound();
    });
});

describe('actions', function () {
    it('requires a signed-in user', function (string $prefix, string $action) {
        $this->postJson("$prefix/keychain/$action")->assertUnauthorized();
    })->with(['admin/actions', 'actions'])->with(ACTIONS);

    it('requires an admin', function (string $prefix, string $action) {
        $this->actingAs($this->cpUser(), 'craft')->postJson("$prefix/keychain/$action")->assertForbidden();
    })->with(['admin/actions', 'actions'])->with(ACTIONS);

    it('requires a CSRF token', function (string $prefix, string $action) {
        $this->enforceCsrf();

        $this->actingAs($this->admin(), 'craft')->post("$prefix/keychain/$action")->assertStatus(419);
    })->with(['admin/actions', 'actions'])->with(ACTIONS);

    it('saves a key pair the user brings', function () {
        $plain = new OpenSSL()->create();

        $this->actingAs($this->admin(), 'craft')->post('admin/actions/keychain/upsert', [
            'description' => 'Brought',
            'key' => $plain->getDecryptedKey(),
            'certificate' => $plain->getDecryptedCertificate(),
            'isEncrypted' => '1',
            'pluginHandle' => 'saml-sp',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $record = KeyChainRecord::query()->where('description', 'Brought')->sole();
        expect($record->class)->toBe(Byok::class)
            ->and($record->pluginHandle)->toBe('saml-sp')
            ->and($record->isEncrypted)->toBeTrue()
            // Craft's TrimStrings middleware drops the PEM's trailing newline, which OpenSSL doesn't need
            ->and($record->getDecryptedKey())->toBe(trim($plain->getDecryptedKey()));
    });

    it('keeps the stored key when the key is left blank', function () {
        $keyPair = savedKeyPair();

        $this->actingAs($this->admin(), 'craft')->post('admin/actions/keychain/upsert', [
            'identifier' => $keyPair->id,
            'description' => 'Renamed',
            'key' => '',
            'certificate' => $keyPair->getDecryptedCertificate(),
            'isEncrypted' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $record = KeyChainRecord::find($keyPair->id);
        expect($record->description)->toBe('Renamed')
            ->and($record->getDecryptedKey())->toBe($keyPair->getDecryptedKey());
    });

    it('rejects a mismatched pair without flashing the key to the session', function () {
        $plain = new OpenSSL()->create();
        $other = new OpenSSL()->create();

        $this->actingAs($this->admin(), 'craft')->from('admin/keychain/new')->post('admin/actions/keychain/upsert', [
            'description' => 'Mismatched',
            'key' => $plain->getDecryptedKey(),
            'certificate' => $other->getDecryptedCertificate(),
            'pluginHandle' => 'keychain',
        ])->assertRedirect('admin/keychain/new')->assertSessionHasErrors(['key']);

        expect(json_encode(session()->all()))->not->toContain(pemLine($plain->getDecryptedKey()))
            ->and(session()->getOldInput('description'))->toBe('Mismatched')
            ->and(KeyChainRecord::query()->where('description', 'Mismatched')->exists())->toBeFalse();
    });

    it('generates an OpenSSL key pair from the form', function () {
        $this->actingAs($this->admin(), 'craft')->post('admin/actions/keychain/upsert/openssl', [
            'description' => 'Generated',
            'commonName' => 'sp.example.test',
            'enabled' => '1',
            'isEncrypted' => '1',
            'plugin' => 'saml-sp',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $record = KeyChainRecord::query()->where('description', 'Generated')->sole();
        expect($record->pluginHandle)->toBe('saml-sp')
            ->and(openssl_x509_parse($record->getDecryptedCertificate())['subject']['CN'])->toBe('sp.example.test');
    });

    it('generates a key pair over JSON without returning key material', function () {
        $response = $this->actingAs($this->admin(), 'craft')
            ->postJson('admin/actions/keychain/upsert/generate-openssl', ['plugin' => 'saml-sp'])
            ->assertOk()
            ->assertJsonStructure(['id', 'description']);

        $record = KeyChainRecord::find($response->json('id'));
        expect($record->pluginHandle)->toBe('saml-sp')
            ->and($response->getContent())->not->toContain('PRIVATE KEY')
            ->and($response->json())->not->toHaveKeys(['key', 'certificate']);
    });

    it('toggles a key pair’s status', function () {
        $keyPair = savedKeyPair();

        $this->actingAs($this->admin(), 'craft')
            ->post('admin/actions/keychain/upsert/change-status', ['identifier' => $keyPair->id])
            ->assertRedirect();

        expect(KeyChainRecord::find($keyPair->id)->enabled)->toBeFalse();
    });

    it('deletes a key pair from the form or the table', function (string $method, string $param) {
        $keyPair = savedKeyPair();

        $this->actingAs($this->admin(), 'craft')
            ->json($method, 'admin/actions/keychain/upsert/delete', [$param => $keyPair->id])
            ->assertOk();

        expect(KeyChainRecord::find($keyPair->id))->toBeNull();
    })->with([
        'form' => ['POST', 'identifier'],
        'table' => ['DELETE', 'id'],
    ]);

    it('404s for a missing key pair', function (string $action) {
        $this->actingAs($this->admin(), 'craft')
            ->postJson("admin/actions/keychain/$action", ['identifier' => 999])
            ->assertNotFound();
    })->with(['upsert/change-status', 'upsert/delete']);
});
