<?php

namespace lewiscom\presto\services;

use Craft;
use const DIRECTORY_SEPARATOR;
use RegexIterator;
use craft\db\Query;
use craft\base\Component;
use lewiscom\presto\Presto;
use craft\helpers\FileHelper;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;


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
            //Craft::$app->templateCaches->deleteAllCaches();

            if ($immediate) {
                $this->purgeEntireCache();
            } else {
                // TODO:
                //$this->storePurgeAllEvent();
            }
        } else if (count($caches) || count($this->caches)) {
            $caches = count($caches) ? $caches : $this->caches;

            if ($immediate) {
                $this->purgeCache(
                    $this->formatPaths($caches)
                );
            } else {
                $this->storePurgeEvent(
                    $this->formatPaths($caches)
                );
            }
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

                if (file_exists($targetPath)) {
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
        $cachePath = $this->getCachePath();

        if (file_exists($cachePath)) {
            FileHelper::clearDirectory($cachePath);
        }
    }

    /**
     * Write the HTML output to a static cache file
     *
     * @param string $host
     * @param string $path
     * @param string $html
     * @param array $config
     * @throws \yii\base\ErrorException
     */
    public function write($host, $path, $html, $config = [])
    {
        $isGuest = Craft::$app->user->isGuest;

        if (! $isGuest && ! $this->settings->cacheWhenLoggedIn) {
            return;
        }

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

            FileHelper::writeToFile($targetFile, $this->cleanHtml($html));
        }
    }

    /**
     * Check if request is a valid get request that is not in live
     * preview mode
     *
     * @return bool
     */
    public function isCacheable()
    {
        $request = Craft::$app->request;

        return http_response_code() === 200 &&
            ! $request->isLivePreview &&
            ! $request->isPost;
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

    /**
     * Format cacheKey arrays into an array of paths
     *
     * @param $caches
     * @return array
     */
    private function formatPaths($caches)
    {
        $paths = [];

        foreach ($caches as $cache) {
            $paths[] = $cache['cacheKey'];
        }

        return $paths;
    }
}
