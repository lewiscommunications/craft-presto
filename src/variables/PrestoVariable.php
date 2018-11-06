<?php

namespace lewiscom\presto\variables;

use Craft;
use craft\web\Application;
use lewiscom\presto\Presto;
use yii\base\Event;

class PrestoVariable
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var string
     */
    public $baseUrl;

    /**
     * @var string
     */
    public $path;

    /**
     * @var mixed
     */
    public $cacheService;

    /**
     * @var array
     */
    public $config;

    /**
     * @var string
     */
    public $key;

    /**
     * @var array
     */
    public $settings;

    /**
     * PrestoVariable constructor.
     */
    public function __construct()
    {
        $plugin = Presto::$plugin;
        $request = Craft::$app->request;

        $this->host = $request->getServerName();
        $this->baseUrl = $request->getBaseUrl();
        $this->path = (! empty($this->baseUrl) ? ltrim($this->baseUrl, '/') . '/' : '')
            . $request->getFullPath();
        $this->cacheService = $plugin->cacheService;
        $this->settings = $plugin->getSettings();
    }

    /**
     * Generate cache for cacheable content when the plugin tag is present
     *
     * @param array $config (optional) [
     *     @var string $group
     *     @var bool $static
     * ]
     * @return string
     */
    public function cache($config = [])
    {
        $this->config = $config;

        // The hostname and url path segments
        $keySegments = [
            'host' => $this->host,
            'path' => $this->path,
        ];

        // Is this part of a group?
        if (isset($config['group']) && $config['group']) {
            $keySegments['group'] = $config['group'];
        }

        // Generate the cache key based on the key segments
        $this->key = $this->cacheService->generateKey($keySegments);

        Event::on(
            Application::class,
            Application::EVENT_AFTER_REQUEST,
            [$this, 'handleAfterRequestEvent']
        );

        return $this->key;
    }

    /**
     * This method will check if the request is cacheable, if so we will
     * create the static html file
     *
     * @throws \yii\base\ErrorException
     */
    public function handleAfterRequestEvent()
    {
        if ($this->cacheService->isCacheable()) {
            if ($html = Craft::$app->templateCaches->getTemplateCache($this->key, true)) {
                $this->cacheService->write([
                    'host' => $this->host,
                    'path' => $this->path,
                    'html' => $html,
                    'config' => $this->config ?? [],
                    'cacheKey' => $this->key
                ]);
            }
        }
    }
}
