<?php
namespace Craft;

class Presto_PrestoCachePurgeRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'presto_cachepurge';
	}

	protected function defineAttributes()
	{
		return [
			'purgedAt' => [
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
				'columns' => 'purgedAt',
				'unique' => false
			]
		];
	}
}