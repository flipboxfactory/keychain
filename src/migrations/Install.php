<?php

namespace flipbox\keychain\migrations;

use craft\base\Plugin;
use craft\db\Migration;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;
use yii\base\InvalidConfigException;
use yii\base\Module;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Install extends Migration
{

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        // Delete tables
        $this->dropTableIfExists(KeyChainRecord::tableName());

        return true;
    }

    /**
     * @param Plugin $plugin
     * @return bool
     */
    public function externalSafeDown(Module $module)
    {
        $result = false;

        /**
         * Is anyone else using this table? if not, it can be removed
         */
        if (! $this->getModule()->unsetKeyChainModule($module)->isKeyChainInUse()) {
            $result = $this->safeDown();
        }

        return $result;
    }

    /**
     * Creates the tables.
     *
     * @return void
     */
    protected function createTables()
    {
        /**
         * Does the table already exist
         */
        try {
            /**
             * if it doesn't exist, this will throw an exception
             */
            KeyChainRecord::getTableSchema();
        } catch (InvalidConfigException $e) {
            $this->createTable(KeyChainRecord::tableName(), [
                'id'          => $this->primaryKey(),
                'key'         => $this->text()->notNull(),
                'certificate' => $this->text()->notNull(),
                'class'       => $this->string()->notNull(),
                'settings'    => $this->text(),
                'enabled'     => $this->boolean()->defaultValue(true)->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'uid'         => $this->uid()
            ]);
            $this->addCommentOnTable(KeyChainRecord::tableName(), \Craft::$app->getModule(KeyChain::MODULE_ID)->getVersion());
        }

    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {

    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {

    }

    /**
     * @return KeyChain
     */
    protected function getModule()
    {
        return \Craft::$app->getModule(KeyChain::MODULE_ID);
    }
}
