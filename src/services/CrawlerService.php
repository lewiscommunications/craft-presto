<?php

namespace lewiscom\presto\services;

use Alc\SitemapCrawler;
use Craft;
use craft\base\Component;
use lewiscom\presto\jobs\WarmCacheTask;
use lewiscom\presto\Presto;

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

    public function init()
    {
        parent::init();

        $this->crawler = new SitemapCrawler();
        $this->cacheService = Presto::$plugin->cacheService;
    }

    public function crawl($url = '')
    {
        $results = $this->crawler->crawl($url);
        $this->urls = array_keys($results);

        $urlKeyMap = [];

        // Iterate over the urls and create a map of `$url => $cacheKey`
        foreach ($this->urls as $url) {
            $parsedUrl = parse_url($url);
            $key = $parsedUrl['host'] .
                preg_replace('/^\//', '|', $parsedUrl['path']);

            if (! preg_match('/\|(\w.*)/', $key)) {
                $key = $key . 'home';
            }

            $urlKeyMap[$url] = $key;
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