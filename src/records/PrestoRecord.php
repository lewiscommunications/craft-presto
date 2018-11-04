<?php

namespace lewiscom\presto\records;

use Craft;
use craft\db\ActiveRecord;
use lewiscom\presto\Presto;

class PrestoRecord extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%presto_prestorecord}}';
    }
}
