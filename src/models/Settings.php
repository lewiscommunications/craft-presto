<?php

namespace lewiscom\presto\models;

use lewiscom\presto\Presto;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    /**
     * The path of the document root
     *
     * @var string
     */
    public $rootPath = '@basePath/public_html';

    /**
     * The name of the cache directory
     *
     * @var string
     */
    public $cachePath = '/cache';

    /**
     * The purge method, either `immediate` or `cron`
     *
     * @var string
     */
    public $purgeMethod = 'immediate';

    /**
     * Whether to write cache if user is logged in to the CMS
     *
     * @var bool
     */
    public $cacheWhenLoggedIn = false;

    /**
     * Warm the cache after save
     *
     * @var bool
     */
    public $warmCache = true;

    /**
     * Validation rules
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['rootPath', 'string'],
            ['rootPath', 'default', 'value' => '@basePath/public_html'],
            ['cachePath', 'string'],
            ['cachePath', 'default', 'value' => '/cache'],
            ['purgeMethod', 'string'],
            ['purgeMethod', 'default', 'value' => 'immediate'],
            ['cacheWhenLoggedIn', 'boolean'],
            ['purgeMethod', 'default', 'value' => false],
        ];
    }
}
