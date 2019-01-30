<?php

namespace lewiscom\presto\records;

use Craft;
use craft\db\ActiveRecord;
use craft\validators\DateTimeValidator;
use lewiscom\presto\Presto;

class PrestoPurgeRecord extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%presto_purge_record}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['purgedAt', 'paths'], 'required'],
            [['purgedAt'], DateTimeValidator::class],
            [['url'], 'string'],
        ];
    }
}
