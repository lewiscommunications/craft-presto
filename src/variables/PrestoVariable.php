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

        $this->host = Craft::$app->request->getServerName();
        $this->baseUrl = Craft::$app->request->getBaseUrl();
        $this->path = (! empty($this->baseUrl) ? ltrim($this->baseUrl, '/') . '/' : '')
            . Craft::$app->request->getFullPath();
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

        $keySegments = [
            'host' => $this->host,
            'path' => $this->path,
        ];

        if (isset($config['group']) && $config['group']) {
            $keySegments['group'] = $config['group'];
        }

        $this->key = $this->generateKey($keySegments);

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
        if (
            (! isset($this->config['static']) || $this->config['static'] !== false) &&
            $this->cacheService->isCacheable()
        ) {
            if ($html = Craft::$app->templateCaches->getTemplateCache($this->key, true)) {
                $this->cacheService->write(
                    $this->host,
                    $this->path,
                    $html,
                    $this->config
                );
            }
        }
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
    private function generateKey($keySegments)
    {
        $group = isset($keySegments['group']) ? $keySegments['group'] . '/' : '';
        $path = $keySegments['path'] ? $keySegments['path'] : 'home';
        $key = $keySegments['host'] . '|' . $group . $path;

        return preg_replace('/\s+/', '', $key);
    }
}
