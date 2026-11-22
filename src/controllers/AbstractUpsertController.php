<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers;

use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Url;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\Byok;
use flipbox\keychain\keypair\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * Form actions for key pairs (`actions/keychain/upsert/…`). Every route requires an admin (see `routes/actions.php`)
 * and CSRF protection, which Craft's web middleware applies to POST and DELETE requests.
 *
 * Responses never contain a private key, and failed submissions never flash one to the session.
 */
abstract class AbstractUpsertController extends AbstractController
{
    /**
     * Saves a "bring your own key" key pair.
     */
    public function index(Request $request): Response
    {
        $keyPair = $request->filled('identifier')
            ? $this->findKeyPair($request->input('identifier'))
            : new Byok(['description' => $request->string('description')->toString()])->create();

        // A blank key keeps the stored one: the edit screen never renders the private key
        $keyPair->setSubmittedKeyPair($request->input('key'), $request->input('certificate'));
        $keyPair->fill([
            'description' => $request->input('description'),
            'isEncrypted' => $request->boolean('isEncrypted'),
            'enabled' => $request->has('enabled') ? $request->boolean('enabled') : $keyPair->enabled,
            'pluginHandle' => $request->input('pluginHandle', $keyPair->pluginHandle),
        ]);

        return $this->saveResponse($request, $keyPair);
    }

    /**
     * Generates and saves an OpenSSL key pair from the submitted distinguished name.
     */
    public function openssl(Request $request): Response
    {
        $keyPair = new OpenSSL(array_filter([
            'description' => $request->input('description'),
            ...$request->only(OpenSSL::DN_FIELDS),
        ], fn ($value) => $value !== null))->create();

        $keyPair->fill([
            'enabled' => $request->boolean('enabled'),
            'isEncrypted' => $request->boolean('isEncrypted'),
            'pluginHandle' => $request->input('plugin'),
        ]);

        return $this->saveResponse($request, $keyPair);
    }

    /**
     * Generates an OpenSSL key pair with the plugin's defaults, e.g. from another plugin's "generate" button.
     */
    public function generateOpenssl(Request $request): Response
    {
        $keyPair = KeyChain::getInstance()->getService()->generateOpenssl([
            'pluginHandle' => $request->input('plugin', $this->getPlugin()->handle),
        ]);

        if ($request->expectsJson()) {
            return $keyPair->exists
                ? new JsonResponse(['id' => $keyPair->id, 'description' => $keyPair->description])
                : $this->asJsonFailure(t('Key pair didn’t save.', category: 'keychain'), ['errors' => $keyPair->errors()->getMessages()]);
        }

        return $keyPair->exists
            ? $this->asSuccess(t('Key pair saved.', category: 'keychain'))
            : $this->asFailure(t('Key pair didn’t save.', category: 'keychain'));
    }

    public function changeStatus(Request $request): Response
    {
        $keyPair = $this->findKeyPair($request->input('identifier'));
        $keyPair->enabled = ! $keyPair->enabled;

        return $this->saveResponse($request, $keyPair);
    }

    public function delete(Request $request): Response
    {
        $keyPair = $this->findKeyPair($request->input('identifier', $request->input('id')));

        if (! KeyChain::getInstance()->getService()->delete($keyPair)) {
            return $this->asFailure(t('Key pair didn’t delete.', category: 'keychain'));
        }

        return $this->asSuccess(t('Key pair deleted.', category: 'keychain'));
    }

    protected function findKeyPair(mixed $id): KeyChainRecord
    {
        abort_unless(is_numeric($id), 404, 'Key pair not found.');

        return KeyChainRecord::query()->whereKey((int) $id)->firstOr(fn () => abort(404, 'Key pair not found.'));
    }

    private function saveResponse(Request $request, KeyChainRecord $keyPair): Response
    {
        if (KeyChain::getInstance()->getService()->save($keyPair)) {
            return $this->asSuccess(
                t('Key pair saved.', category: 'keychain'),
                redirect: $this->getPostedRedirectUrl() ?? Url::cpUrl($this->getBaseCpPath()),
            );
        }

        $errors = array_filter($keyPair->errors()->getMessages());

        if ($request->expectsJson()) {
            return $this->asJsonFailure(t('Key pair didn’t save.', category: 'keychain'), ['errors' => $errors]);
        }

        // Not asFailure(): that flashes all input, and the private key must never reach the session
        $request->flashExcept(['key']);
        Flash::error(t('Key pair didn’t save.', category: 'keychain'));

        return back()->withErrors($errors);
    }
}
