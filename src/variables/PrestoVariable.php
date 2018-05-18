<?php

namespace lewiscom\presto\variables;

use Craft;
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
    public $service;

    /**
     * @var array
     */
    public $config;

    /**
     * @var string
     */
    public $key;

    /**
     * PrestoVariable constructor.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct()
    {
        $this->host = Craft::$app->request->getServerName();
        $this->baseUrl = Craft::$app->request->getBaseUrl();
        $this->path = (! empty($this->baseUrl) ? ltrim($this->baseUrl, '/') . '/' : '') . Craft::$app->request->getPathInfo();
        $this->service = Presto::getInstance()->prestoService;
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

        Craft::$app->on(
            Craft::$app::EVENT_AFTER_REQUEST,
            [$this, 'handleAfterRequestEvent']
        );

        return $this->key;
    }

    /**
     * This method will check if the request is cacheable, if so we will
     * create the static html file
     */
    public function handleAfterRequestEvent()
    {
        if (
            (! isset($this->config['static']) || $this->config['static'] !== false) &&
            $this->isCacheable()
        ) {
            if ($html = Craft::$app->templateCaches->getTemplateCache($this->key, true)) {
                $this->service->writeCache(
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

    /**
     * Check if request is a valid get request that is not in live
     * preview mode
     *
     * @return bool
     */
    private function isCacheable()
    {
        $request = Craft::$app->request;

        return http_response_code() === 200 &&
            ! $request->isLivePreview &&
            ! $request->isPost;
    }
}
