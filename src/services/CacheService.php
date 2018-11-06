<?php

namespace lewiscom\presto\services;

use Craft;
use RegexIterator;
use craft\db\Query;
use yii\base\Event;
use craft\base\Component;
use lewiscom\presto\Presto;
use craft\helpers\FileHelper;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use lewiscom\presto\events\PurgeEvent;
use lewiscom\presto\events\CacheEvent;

class CacheService extends Component
{
    /**
     * @var mixed
     */
    public $settings;

    /**
     * @var array
     */
    public $caches = [];

    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();

        $this->settings = Presto::$plugin->getSettings();
    }

    /**
     * Triggers purging the Presto cache, checking whether to trigger immediate or cron
     *
     * @param bool $all
     * @param array $caches
     * @throws \yii\base\ErrorException
     */
    public function triggerPurge($all = false, $caches = [])
    {
        $immediate = $this->settings->purgeMethod === 'immediate';

        if ($all) {
            if ($immediate) {
                $this->purgeEntireCache();
            } else {
                // TODO:
                //$this->storePurgeAllEvent();
            }
        } else if (count($caches) || count($this->caches)) {
            $caches = count($caches) ? $caches : $this->caches;
            $keys = array_column($caches, 'cacheKey');

            Event::trigger(
                Presto::class,
                Presto::EVENT_BEFORE_PURGE_CACHE,
                new PurgeEvent([
                    'cacheKeys' => $keys,
                ])
            );

            Craft::$app->templateCaches->deleteCachesByKey($caches);

            if ($immediate) {
                $this->purgeCache($keys);
            } else {
                // TODO
                //$this->storePurgeEvent(
                //    $this->formatPaths($caches)
                //);
            }

            Event::trigger(
                Presto::class,
                Presto::EVENT_AFTER_PURGE_CACHE,
                new PurgeEvent()
            );
        }
    }

    /**
     * Purge cached files by path
     *
     * @param array $paths
     * @throws \yii\base\ErrorException
     */
    public function purgeCache($paths = [])
    {
        if (count($paths)) {
            foreach ($paths as $path) {
                $url = explode('|', $path, 2);

                if (count($url) < 2) {
                    continue;
                }

                $targetPath = $this->getTargetPath($url);

                $targetFile = $targetPath . DIRECTORY_SEPARATOR . 'index.html';

                if (file_exists($targetFile)) {
                    FileHelper::unlink($targetFile);
                }

                // TODO: When deleting a single cache key that is an index of other entries,
                // we need to check if that directory contains other directories or the entire
                // section cache will be deleted.  Alternative methods may be desired.
                if (file_exists($targetPath) && ! count(FileHelper::findDirectories($targetPath))) {
                    FileHelper::removeDirectory($targetPath);
                }
            }
        }
    }

    /**
     * Set the caches array
     *
     * @param array $ids
     */
    public function setCaches(array $ids = [])
    {
        $this->caches = $this->getRelatedTemplateCaches($ids);
    }

    /**
     * Generates urls from cache keys
     *
     * @param array $caches
     * @return array
     */
    public function getUrlsFromCacheKeys(array $caches = [])
    {
        $caches = count($caches) ? $caches : $this->caches;

        return array_map(function($key) {
            return preg_replace('/\|(home)?/', '', $key);
        }, array_column($caches, 'cacheKey'));
    }

    /**
     * Gets all of the cache keys
     *
     * @return array
     */
    public function getAllCacheKeys()
    {
        $results = (new Query())
            ->select('cacheKey')
            ->from(['{{%templatecaches}}'])
            ->distinct()
            ->all();

        return array_column($results, 'cacheKey');
    }

    /**
     * Get template caches by elementId
     *
     * @param array|string $elementIds
     * @return array
     */
    public function getRelatedTemplateCaches($elementIds)
    {
        return (new Query())
            ->select('cacheKey')
            ->from(['{{%templatecaches}} AS caches'])
            ->join(
                'JOIN',
                '{{%templatecacheelements}} as elements',
                'caches.id = elements.cacheId'
            )
            ->where([
                'in',
                'elements.elementId',
                $elementIds
            ])
            ->distinct()
            ->all();
    }

    /**
     * Purge all cached files
     *
     * @throws \yii\base\ErrorException
     */
    public function purgeEntireCache()
    {
        Event::trigger(
            Presto::class,
            Presto::EVENT_BEFORE_PURGE_CACHE_ALL,
            new PurgeEvent()
        );
        // Delete all of the caches in the Craft template cache table
        Craft::$app->templateCaches->deleteAllCaches();

        $cachePath = $this->getCachePath();

        if (file_exists($cachePath)) {
            FileHelper::clearDirectory($cachePath);
        }

        Event::trigger(
            Presto::class,
            Presto::EVENT_AFTER_PURGE_CACHE_ALL,
            new PurgeEvent()
        );
    }

    /**
     * Write the HTML output to a static cache file
     *
     * @param array $options [
     *     @param string $host
     *     @param string $path
     *     @param string $html
     *     @param array $config
     *     @param string $cachKey
     * ]
     * @throws \yii\base\ErrorException
     */
    public function write($options = [])
    {
        [
            'host' => $host,
            'path' => $path,
            'html' => $html,
            'config' => $config,
            'cacheKey' => $cacheKey
        ] = $options;

        $isGuest = Craft::$app->user->isGuest;

        if (! $isGuest && ! $this->settings->cacheWhenLoggedIn) {
            return;
        }

        Event::trigger(
            Presto::class,
            Presto::EVENT_BEFORE_GENERATE_CACHE_ITEM,
            new CacheEvent([
                'host' => $host,
                'path' => $path,
                'html' => $html,
                'config' => $config,
                'cacheKey' => $cacheKey
            ])
        );

        if (! isset($config['static']) || $config['static'] !== false) {
            $pathSegments = array_merge(
                $this->getCachePath(true),
                [$host, 'presto']
            );

            if (isset($config['group'])) {
                $pathSegments[] = $config['group'];
            }

            $pathSegments[] = $path;

            $targetPath = $this->normalizePath(
                implode(DIRECTORY_SEPARATOR, $pathSegments)
            );

            $pathInfo = pathinfo($targetPath);

            $extension = isset($pathInfo['extension']) ?
                $pathInfo['extension'] : 'html';

            $targetFile = $targetPath . DIRECTORY_SEPARATOR . 'index.' . $extension;

            // TODO: Check if writeable `is_writable($dir)`
            FileHelper::writeToFile($targetFile, $this->cleanHtml($html));

            Event::trigger(
                Presto::class,
                Presto::EVENT_AFTER_GENERATE_CACHE_ITEM,
                new CacheEvent([
                    'html' => $html,
                    'cacheKey' => $cacheKey,
                    'filePath' => $targetFile,
                    'host' => $host,
                    'path' => $path
                ])
            );
        }
    }

    /**
     * Check if request is a valid get request that is not in live
     * preview mode and th
     *
     * @return bool
     */
    public function isCacheable(array $config = [])
    {
        $request = Craft::$app->request;

        return http_response_code() === 200 &&
            ! $request->isLivePreview &&
            ! $request->isPost && (! isset($config['static']) ||
            isset($config['static']) && $config['static'] !== false);
    }

    public function hasCaches()
    {
        return count($this->caches);
    }

    /**
     * Returns the cache path
     *
     * @param bool $arr - get the path in array
     * @return string|array
     */
    public function getCachePath(bool $arr = false)
    {
        $path = [
            Craft::getalias($this->settings->rootPath, false),
            Craft::getAlias($this->settings->cachePath)
        ];

        return $arr ? $path : implode('', $path);
    }

    /**
     * Returns the total number of cached static file templates
     *
     * @return int
     */
    public function getStaticCacheFileCount()
    {
        $dir = new RecursiveDirectoryIterator(Presto::$plugin->cacheService->getCachePath());
        $iterator = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($iterator, '/.*html$/', RegexIterator::GET_MATCH);
        $fileList = [];

        foreach ($files as $file) {
            $fileList = array_merge($fileList, $file);
        }

        return count($fileList);
    }

    /**
     * Generate cacheKey based on the host and path
     *
     * @param array $keySegments [
     *		@var string $host
     *		@var string $path
     * 		@var string $group (optional)
     * ]
     * @return string
     */
    public function generateKey($keySegments)
    {
        $group = isset($keySegments['group']) ? $keySegments['group'] . '/' : '';
        $path = $keySegments['path'] ? $keySegments['path'] : 'home';
        $key = $keySegments['host'] . '|' . $group . $path;

        return preg_replace('/\s+/', '', $key);
    }

    /**
     * De-duplicate slashes in a file system path
     *
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        $pattern = '~' . DIRECTORY_SEPARATOR . '+~';

        return rtrim(
            preg_replace($pattern, DIRECTORY_SEPARATOR, $path),
            DIRECTORY_SEPARATOR
        );
    }

    /**
     * Builds the target file path based on the cache key
     *
     * @param array $url
     * @return string
     */
    private function getTargetPath(array $url)
    {
        $path = array_merge($this->getCachePath(true), [
            $url[0],
            'presto',
            str_replace('home', '', $url[1])
        ]);

        return $this->normalizePath(implode(DIRECTORY_SEPARATOR, $path));
    }

    /**
     * Removes unwanted entities or whitespace from HTML
     *
     * @param string $html
     * @return string
     */
    private function cleanHtml(string $html)
    {
        return trim(str_replace(
            [
                '<![CDATA[YII-BLOCK-HEAD]]>',
                '<![CDATA[YII-BLOCK-BODY-BEGIN]]>',
                '<![CDATA[YII-BLOCK-BODY-END]]>',
            ],
            '',
            $html
        ));
    }
}
