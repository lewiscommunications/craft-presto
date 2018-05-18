<?php

namespace lewiscom\presto\records;

use craft\db\ActiveRecord;

class PrestoCachePurgeRecord extends ActiveRecord
{
     /**
     * @return string the table name
     */
    public static function tableName()
    {
        return '{{%presto_cachepurge}}';
    }
}
