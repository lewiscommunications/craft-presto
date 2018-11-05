<?php

namespace lewiscom\presto\records;

use Craft;
use craft\db\ActiveRecord;
use lewiscom\presto\Presto;

class PrestoCacheRecord extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%presto_cache_record}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url'], 'required'],
            [['type'], 'string'],
            [['url'], 'string'],
        ];
    }
}
