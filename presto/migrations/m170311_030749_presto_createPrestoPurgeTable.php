<?php
namespace Craft;

class m170311_030749_presto_createPrestoPurgeTable extends BaseMigration
{
	/**
	 * @return bool
	 */
	public function safeUp()
	{
		$command = craft()->db->createCommand();
		$command->createTable('presto_cachepurge', [
			'purgedAt' => ['column' => 'datetime', 'required' => true],
			'paths' => ['required' => true],
		], null, true);
		$command->createIndex('presto_cachepurge', 'purgedAt', false);

		// Update rootPath setting
		craft()->plugins->savePluginSettings(
			craft()->plugins->getPlugin('Presto'), [
				'cachePath' => craft()->request->getPost('cachePath'),
				'rootPath' => craft()->config->get('rootPath', 'presto')
			]
		);

		return true;
	}
}