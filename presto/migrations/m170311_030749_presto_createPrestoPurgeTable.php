<?php
namespace Craft;

class m170311_030749_presto_createPrestoPurgeTable extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		craft()->db->createCommand()
			->createTable('presto_cachepurge', [
				'purgedAt' => ['column' => 'datetime', 'required' => true],
				'paths' => ['required' => true],
			], null, true);

		craft()->db->createCommand()
			->createIndex('presto_cachepurge', 'purgedAt', false);

		// Update root path
		craft()->plugins->savePluginSettings(
			craft()->plugins->getPlugin('Presto'), [
				'rootPath' => craft()->config->get('rootPath', 'presto')
			]
		);

		return true;
	}
}