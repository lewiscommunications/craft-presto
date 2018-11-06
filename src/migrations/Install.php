<?php

namespace lewiscom\presto\migrations;

use Craft;
use craft\db\Migration;
use craft\config\DbConfig;
use lewiscom\presto\Presto;
use lewiscom\presto\records\PrestoCacheRecord;
use lewiscom\presto\records\PrestoPurgeRecord;

class Install extends Migration
{
    /**
     * @var string The database driver to use
     */
    public $driver;

    /**
     * @var string
     */
    public $cacheRecordTableName;

    /**
     * @var string
     */
    public $purgeRecordTableName;

    /**
     * @return bool
     */
    public function safeUp()
    {
        $this->cacheRecordTableName = PrestoCacheRecord::tableName();
        $this->purgeRecordTableName = PrestoPurgeRecord::tableName();

        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $cacheRecordTableSchema = Craft::$app->db->schema
            ->getTableSchema($this->cacheRecordTableName);
        $purgeRecordtableSchema = Craft::$app->db->schema
            ->getTableSchema($this->purgeRecordTableName);

        if (
            $cacheRecordTableSchema === null &&
            $purgeRecordtableSchema === null
        ) {
            $tablesCreated = true;

            $this->createTable(
                $this->cacheRecordTableName,
                [
                    'id' => $this->primaryKey(),
                    'siteId' => $this->integer()->notNull(),
                    'cacheKey' => $this->string()->notNull(),
                    'filePath' => $this->string()->notNull(),
                    'url' => $this->string()->notNull(),
                    'group' => $this->string(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );

            $this->createTable(
                $this->purgeRecordTableName,
                [
                    'id' => $this->primaryKey(),
                    'siteId' => $this->integer()->notNull(),
                    'purgedAt' => $this->dateTime()->notNull(),
                    'paths' => $this->text()->notNull(),
                    'group' => $this->string(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                $this->cacheRecordTableName,
                'id',
                true
            ),
            $this->cacheRecordTableName,
            'id',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                $this->purgeRecordTableName,
                'purgedAt',
                false
            ),
            $this->purgeRecordTableName,
            'purgedAt',
            false
        );
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        // Add siteId foreign key to cache record table
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->cacheRecordTableName, 'siteId'),
            $this->cacheRecordTableName,
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Add cacheId key to cache record table
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->cacheRecordTableName, 'cacheId'),
            $this->cacheRecordTableName,
            'cacheKey',
            '{{%templatecaches}}',
            'cacheKey',
            'CASCADE',
            'CASCADE'
        );

        // Add siteId foreign key to purge record table
        $this->addForeignKey(
            $this->db->getForeignKeyName($this->purgeRecordTableName, 'siteId'),
            $this->purgeRecordTableName,
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists(PrestoPurgeRecord::tableName());
        $this->dropTableIfExists(PrestoCacheRecord::tableName());
    }
}
