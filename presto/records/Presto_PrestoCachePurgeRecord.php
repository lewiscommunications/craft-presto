<?php
namespace Craft;

class Presto_PrestoCachePurgeRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'prestocachepurge';
	}

	protected function defineAttributes()
	{
		return [
			'purged_at' => [
				AttributeType::DateTime,
				'required' => true
			],
			'paths' => [
				AttributeType::String,
				'required' => true
			]
		];
	}

	public function defineIndexes()
	{
		return [
			[
				'columns' => 'purged_at',
				'unique' => true
			]
		];
	}
}