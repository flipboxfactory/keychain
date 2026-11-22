<?php

declare(strict_types=1);

namespace flipbox\keychain\controllers\cp\view;

use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use flipbox\keychain\controllers\cp\AbstractController;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;

use function CraftCms\Cms\t;

/**
 * Lists the plugin's key pairs.
 */
abstract class AbstractGeneralController extends AbstractController
{
    public function index(): CpScreenResponse
    {
        $keyPairs = KeyChain::getInstance()->getService()
            ->findByPlugin($this->getPlugin())
            ->orderBy('id')
            ->get();

        $table = Table::make('keychain-key-pairs')
            ->columns([
                ['key' => 'description', 'label' => t('Description', category: 'keychain'), 'sortable' => true, 'width' => '2fr'],
                ['key' => 'type', 'label' => t('Type', category: 'keychain')],
                ['key' => 'dateCreated', 'label' => t('Date Created')],
                ['key' => 'dateUpdated', 'label' => t('Date Updated')],
            ])
            ->rows($keyPairs->map(fn (KeyChainRecord $keyPair) => [
                'id' => $keyPair->id,
                '_status' => $keyPair->isEnabled(),
                'description' => [
                    'label' => $keyPair->description ?: t('Key pair', category: 'keychain'),
                    'url' => Url::cpUrl($this->getBaseCpPath().'/'.$keyPair->id),
                ],
                'type' => class_basename($keyPair->class),
                'dateCreated' => ['date' => $keyPair->dateCreated?->toIso8601String()],
                'dateUpdated' => ['date' => $keyPair->dateUpdated?->toIso8601String()],
                '_deleteConfirmMessage' => t('Are you sure you want to delete “{name}”? Anything that signs or decrypts with it will stop working.', [
                    'name' => $keyPair->description ?: t('Key pair', category: 'keychain'),
                ], 'keychain'),
            ]))
            ->emptyMessage(t('No key pairs exist yet.', category: 'keychain'))
            ->createActionMenu(t('New key pair', category: 'keychain'), [
                ['label' => t('Bring your own key', category: 'keychain'), 'url' => Url::cpUrl($this->getBaseCpPath().'/new')],
                ['label' => t('Generate OpenSSL key pair', category: 'keychain'), 'url' => Url::cpUrl($this->getBaseCpPath().'/openssl')],
            ])
            ->createActionInPageHeader()
            ->deletable($this->actionUrl('upsert/delete'));

        return new CpScreenResponse()
            ->title($this->title())
            ->crumbs(array_slice($this->crumbs(), 0, -1))
            ->ui(Ui::make([$table]));
    }
}
