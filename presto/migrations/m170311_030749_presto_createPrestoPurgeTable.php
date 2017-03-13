<?php
namespace Craft;

class m170311_030749_presto_createPrestoPurgeTable extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		craft()->db->createCommand()->createTable('presto_cachepurge', [
			'purged_at' => ['column' => 'datetime', 'required' => true],
			'paths' => ['required' => true],
		], null, true);

		craft()->db->createCommand()->createIndex('presto_cachepurge', 'purged_at', true);

		return true;
	}
}