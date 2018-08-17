<?php

namespace flipbox\keychain\migrations;

use craft\db\Migration;
use flipbox\keychain\KeyChain;
use flipbox\keychain\records\KeyChainRecord;
use yii\base\InvalidConfigException;

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
                'id'           => $this->primaryKey(),
                'description'  => $this->text()->comment(
                    'User defined description so a human can understand what this is for.'
                ),
                'key'          => $this->text()->notNull(),
                'certificate'  => $this->text()->notNull(),
                'class'        => $this->string()->notNull(),
                'pluginHandle' => $this->string()->notNull(),
                'settings'     => $this->text(),
                'enabled'      => $this->boolean()->defaultValue(true)->notNull(),
                'isEncrypted'  => $this->boolean()->defaultValue(true)->notNull(),
                'dateUpdated'  => $this->dateTime()->notNull(),
                'dateCreated'  => $this->dateTime()->notNull(),
                'uid'          => $this->uid()
            ]);
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
}
