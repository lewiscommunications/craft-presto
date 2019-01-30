<?php

namespace lewiscom\presto\services;

use Craft;
use Alc\SitemapCrawler;
use craft\base\Component;
use lewiscom\presto\Presto;
use lewiscom\presto\jobs\WarmCacheTask;

/**
 * Class CrawlerService
 *
 * @package lewiscom\presto\services
 * @property SitemapCrawler $crawler
 */
class CrawlerService extends Component
{

    /**
     * @var SitemapCrawler
     */
    public $crawler;

    /**
     * @var CacheService
     */
    public $cacheService;

    /**
     * @var array
     */
    public $urls = [];

    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();

        $this->crawler = new SitemapCrawler();
        $this->cacheService = Presto::$plugin->cacheService;
    }

    /**
     * Crawl a specific url
     *
     * @param string $url
     */
    public function crawl($url = '')
    {
        $results = $this->crawler->crawl($url);
        $this->urls = array_keys($results);

        $urlKeyMap = [];

        // Iterate over the urls and create a map of `$url => $cacheKey`
        foreach ($this->urls as $url) {
            $pathInfo = parse_url($url);
            $pathInfo['path'] =  ltrim($pathInfo['path'], '/');
            $urlKeyMap[$url] = $this->cacheService->generateKey($pathInfo);
        }

        $existingKeys = $this->cacheService->getAllCacheKeys();

        // Remove any urls that exi
        foreach ($urlKeyMap as $url => $key) {
            if (in_array($key, $existingKeys)) {
                unset($urlKeyMap[$url]);
            }
        }

        // Grab all the urls
        $toWarm = array_keys($urlKeyMap);

        // Queue the task
        Craft::$app
            ->getQueue()
            ->push(new WarmCacheTask([
                'urls' => $toWarm,
            ]));
    }
}