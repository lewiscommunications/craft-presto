<?php

namespace lewiscom\presto\models;

use craft\base\Model;
use yii\behaviors\AttributeBehavior;

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
    // TODO: Possibly change this to `warmCacheOnSave`
    public $warmCache = true;

    /**
     * The location of the site map index to crawl
     *
     * @var string
     */
    public $sitemapIndex = '@cdnUrl/sitemaps/index.xml';

    /**
     * Show in main CP navigation
     *
     * @var bool
     */
    public $showInCpNav = false;

    /**
     * Clear entire cache when these sections are updated
     *
     * @var array
     */
    public $sections = [];

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
            ['sitemapIndex', 'string'],
            ['sitemapIndex', 'default', 'value' => '@cdnUrl/sitemaps/index.xml'],
            ['showInCpNav', 'boolean'],
            ['showInCpNav', 'default', 'value' => false],
            ['cacheWhenLoggedIn', 'boolean'],
            ['purgeMethod', 'default', 'value' => false],
        ];
    }
}
