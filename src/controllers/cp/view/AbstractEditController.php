<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers\cp\view;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\keypair\traits\OpenSSL;
use flipbox\keychain\records\KeyChainRecord;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

use function CraftCms\Cms\t;

/**
 * The "bring your own key" edit screen and the OpenSSL generation screen.
 *
 * The private key is never sent to the browser: the key field is always empty, and leaving it empty keeps the
 * stored key.
 */
abstract class AbstractEditController extends AbstractController
{
    use OpenSSL;

    public function index(Request $request, ?int $keypairId = null): CpScreenResponse
    {
        $keyPair = $keypairId
            ? KeyChainRecord::query()->whereKey($keypairId)->firstOr(fn () => abort(404, 'Key pair not found.'))
            : null;

        $ui = Ui::make([
            HiddenField::make('pluginHandle'),
            ...($keyPair ? [HiddenField::make('identifier')] : []),
            Field::make(t('Description', category: 'keychain'), Text::make('description'))
                ->instructions(t('What this key pair is for.', category: 'keychain')),
            Field::make(t('Private Key', category: 'keychain'), Textarea::make('key')
                ->monospace()
                ->rows(10)
                ->placeholder($keyPair ? '••••••••••••••••••••••••••••••••' : '-----BEGIN PRIVATE KEY-----'))
                ->instructions($keyPair
                    ? t('The PEM-encoded private key. It’s hidden: leave this blank to keep the current key, or paste a new one to replace it.', category: 'keychain')
                    : t('The PEM-encoded private key.', category: 'keychain'))
                ->required(! $keyPair),
            Field::make(t('Certificate', category: 'keychain'), Textarea::make('certificate')->monospace()->rows(10))
                ->instructions(t('The PEM-encoded X.509 certificate for the private key.', category: 'keychain'))
                ->required(),
            Field::make(t('Enabled'), Lightswitch::make('enabled')),
            Field::make(t('Encrypt at Rest', category: 'keychain'), Lightswitch::make('isEncrypted'))
                ->instructions(t('Encrypt the key and certificate in the database with the application key.', category: 'keychain')),
        ]);

        $values = [
            'pluginHandle' => $this->getPlugin()->handle,
            'identifier' => $keyPair?->id,
            'description' => $keyPair?->description,
            'key' => '',
            'certificate' => $keyPair?->getDecryptedCertificate(),
            'enabled' => $keyPair->enabled ?? true,
            'isEncrypted' => $keyPair->isEncrypted ?? KeyChain::getInstance()->getSettings()->encryptByDefault,
        ];

        $response = $this->screen($request, $ui, $values, 'upsert')
            ->title($this->title($keyPair ? t('Edit', category: 'keychain') : t('Create Bring Your Own Key', category: 'keychain')))
            ->crumbs([...$this->crumbs(), new ActionItem()->label($keyPair
                ? ($keyPair->description ?: t('Key pair', category: 'keychain'))
                : t('New', category: 'keychain'))]);

        return $response;
    }

    public function openssl(Request $request): CpScreenResponse
    {
        $fields = [];
        foreach ($this->getAttributes() as $attribute => $options) {
            $fields[] = Field::make($this->labels[$attribute], Text::make($attribute)->placeholder($options['default']));
        }

        $ui = Ui::make([
            HiddenField::make('plugin'),
            ...$fields,
            Field::make(t('Enabled'), Lightswitch::make('enabled')),
            Field::make(t('Encrypt at Rest', category: 'keychain'), Lightswitch::make('isEncrypted'))
                ->instructions(t('Encrypt the key and certificate in the database with the application key.', category: 'keychain')),
        ]);

        $values = [
            'plugin' => $this->getPlugin()->handle,
            'enabled' => true,
            'isEncrypted' => KeyChain::getInstance()->getSettings()->encryptByDefault,
        ];

        return $this->screen($request, $ui, $values, 'upsert/openssl')
            ->title($this->title(t('Create OpenSSL Key Pair', category: 'keychain')))
            ->crumbs([...$this->crumbs(), new ActionItem()->label(t('New', category: 'keychain'))]);
    }

    /**
     * A form screen posting to one of keychain's actions, with any values and errors from a failed submission.
     *
     * @param  array<string, mixed>  $values
     */
    private function screen(Request $request, Ui $ui, array $values, string $action): CpScreenResponse
    {
        // The private key is never flashed to the session, so a failed submission can't bring it back here
        $old = array_diff_key((array) $request->old(), ['key' => true]);
        $errors = $request->session()->get('errors');

        return new CpScreenResponse()
            ->redirectUrl($this->getBaseCpPath())
            ->ui($ui, new UiContext(
                values: [...$values, ...array_intersect_key($old, $values)],
                errors: $errors instanceof ViewErrorBag ? $errors->getBag('default')->getMessages() : [],
            ), [
                'submit' => [
                    'method' => 'post',
                    'url' => $this->actionUrl($action),
                ],
                'redirect' => Url::cpUrl($this->getBaseCpPath()),
            ]);
    }
}
