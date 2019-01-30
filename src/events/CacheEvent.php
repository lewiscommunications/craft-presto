<?php

namespace lewiscom\presto\events;

use yii\base\Event;

class CacheEvent extends Event
{
    /**
     * The generated HTML
     *
     * @var string
     */
    public $html = '';

    /**
     * The generated cacheKey
     *
     * @var string
     */
    public $cacheKey = '';

    /**
     * The file path for the cached item
     *
     * @var string
     */
    public $filePath = '';

    /**
     * The host
     *
     * @var string
     */
    public $host = '';

    /**
     * The path
     *
     * @var string
     */
    public $path = '';

    /**
     * The configuration for the cached item
     *
     * @var array
     */
    public $config = [];
}
