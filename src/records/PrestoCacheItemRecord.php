<?php

namespace lewiscom\presto\records;

use Craft;
use craft\db\ActiveRecord;
use lewiscom\presto\Presto;

class PrestoCacheItemRecord extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%presto_cache_item_record}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'cacheKey'], 'required'],
            [['cacheKey', 'url', 'cacheGroup', 'filePath'], 'string'],
        ];
    }
}
