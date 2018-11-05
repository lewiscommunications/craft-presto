<?php

namespace lewiscom\presto\events;

use yii\base\Event;

class PurgeEvent extends Event
{
    public $cacheKeys = [];
}
