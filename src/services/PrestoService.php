<?php

namespace lewiscom\presto\services;

use Craft;
use craft\helpers\DateTimeHelper;
use lewiscom\presto\Presto;
use craft\base\Component;
use craft\helpers\FileHelper;
use lewiscom\presto\records\PrestoCachePurgeRecord;
use yii\db\Query;

class PrestoService extends Component
{
    /**
     * @var mixed
     */
    public $settings;

    /**
     * @var
     */
    public $rootPath;

    /**
     * PrestoService constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->settings = Presto::getInstance()->getSettings();
        $this->rootPath = $this->settings->rootPath;
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
    public function writeCache($host, $path, $html, $config = [])
    {
        if (! Craft::$app->user->isGuest) {
            return;
        }

        if (! isset($config['static']) || $config['static'] !== false) {
            $pathSegments = [
                $this->rootPath,
                $this->settings->cachePath,
                $host,
                'presto'
            ];

            if (isset($config['group'])) {
                $pathSegments[] = $config['group'];
            }

            $pathSegments[] = $path;

            $targetPath = $this->normalizePath(
                implode('/', $pathSegments)
            );

            $pathInfo = pathinfo($targetPath);
            $extension = isset($pathInfo['extension']) ?
                $pathInfo['extension'] : 'html';

            $targetFile = $targetPath . '/index.' . $extension;

            $html = str_replace(
                [
                    '<![CDATA[YII-BLOCK-HEAD]]>',
                    '<![CDATA[YII-BLOCK-BODY-BEGIN]]>',
                    '<![CDATA[YII-BLOCK-BODY-END]]>',
                ],
                '',
                $html
            );

            FileHelper::writeToFile($targetFile, trim($html));
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

                $directory = $this->normalizePath(implode('/', [
                    $this->rootPath,
                    $this->settings->cachePath,
                    $url[0],
                    'presto',
                    str_replace('home', '', $url[1])
                ]));

                if (! file_exists($directory)) {
                    continue;
                }

                $targetFile = file_exists($targetPath . '/index.html');

                if ($targetFile) {
                    @unlink($targetFile);
                }

                if ($targetPath) {
                    @rmdir($targetPath);
                }
            }
        }
    }

    /**
     * Purge all cached files
     *
     * @throws \yii\base\ErrorException
     */
    public function purgeEntireCache()
    {
        $cachePath = $this->rootPath . $this->settings->cachePath;

        if (file_exists($cachePath)) {
            FileHelper::clearDirectory($cachePath);
        }
    }

    /**
     * Record the need to bust the entire cache
     */
    public function storePurgeAllEvent()
    {
        $this->storeEvent('all');
    }

    /**
     * Record specific cache paths that need to be busted
     *
     * @param array $paths
     */
    public function storePurgeEvent($paths = [])
    {
        if (count($paths)) {
            $this->storeEvent(serialize($paths));
        }
    }

    /**
     * Format cacheKey arrays into an array of paths
     *
     * @param $caches
     * @return array
     */
    public function formatPaths($caches)
    {
        $paths = [];

        foreach ($caches as $cache) {
            $paths[] = $cache['cacheKey'];
        }

        return $paths;
    }

    /**
     * Update root path
     *
     * @param string $path
     */
    public function updateRootPath($path)
    {
        if (file_exists($path)) {
            $this->rootPath = $path;
        }
    }

    /**
     * Get date time
     *
     * @param null $setStamp
     * @return \DateTime
     */
    public function getDateTime($setStamp = null)
    {
        $stamp = $setStamp ? $setStamp : 'now';

        return new \DateTime($stamp, new \DateTimeZone(Craft::$app->getTimeZone()));
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
     * @param $dateTime
     * @return array
     */
    public function getPurgeEvents($dateTime)
    {
        $paths = [];

        $events = PrestoCachePurgeRecord::find()
            ->where(['>=', 'purgedAt', $dateTime->format('Y-m-d H:i:s')])
            ->all();

        foreach ($events as $event) {
            if ($event->paths === 'all') {
                return ['all'];
            }

            $paths = array_merge($paths, unserialize($event->paths));
        }

        sort($paths);

        return $paths;
    }

    /**
     * Store recorded events for the static cache
     *
     * @param $paths
     */
    private function storeEvent($paths)
    {
        $event = new PrestoCachePurgeRecord();
        $event->setAttribute('purgedAt', $this->getDateTime());
        $event->setAttribute('paths', $paths);
        $event->setAttribute('siteId', Craft::$app->sites->currentSite->id);
        $event->save();
    }

    /**
     * De-duplicate slashes in a file system path
     *
     * @param string $path
     * @return string
     */
    private function normalizePath($path)
    {
        return rtrim(preg_replace('~/+~', '/', $path), '/');
    }
}
