<?php

namespace lewiscom\presto\controllers;

use Craft;
use craft\web\Controller;
use lewiscom\presto\Presto;
use craft\helpers\UrlHelper;

/**
 * Class DefaultController
 *
 * @package lewiscom\presto\controllers
 * @property CacheService $cacheService
 * @property CrawlerService $crawlerService
 */
class DefaultController extends Controller
{
    /**
     * @var CacheService
     */
    public $cacheService;

    /**
     * @var CrawlerService
     */
    public $crawlerService;

    /**
     * @var array
     */
    public $settings;

    public function init()
    {
        parent::init();

        $this->cacheService = Presto::$plugin->cacheService;
        $this->crawlerService = Presto::$plugin->crawlerService;
        $this->settings = Presto::$plugin->getSettings();
    }

    /**
     * Purge the cache
     *
     * @return \yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionPurgeCache()
    {
        $this->requireAdmin();

        $cron = $this->settings->purgeMethod === 'cron';

        if ($cron) {
            // TODO:
            //$cacheService->storePurgeAllEvent();
        } else {
            // Clear db template cache
            Craft::$app->templateCaches->deleteAllCaches();

            // Clear static cache
            $this->cacheService->purgeEntireCache();
        }

        Craft::$app->session->setNotice(
            Presto::t($cron ? 'Cache purge scheduled.' : 'Cache purged.')
        );

        return $this->redirectBack();
    }

    /**
     * Warm the entire cache
     */
    public function actionWarmEntireCache()
    {
        $this->crawlerService->crawl(
            Craft::getAlias($this->settings->sitemapIndex, false)
        );

        Craft::$app->session->setNotice(
            Presto::t('Cache warming has started')
        );

        return $this->redirectBack();
    }

    /**
     * @return \yii\web\Response
     */
    private function redirectBack()
    {
        // Figure out if request came from dashboard or widget
        $referrer = explode('/', Craft::$app->request->referrer);
        $path = end($referrer) === 'dashboard'
            ? '/dashboard'
            : '/cms/presto/settings/general';

        return $this->redirect(UrlHelper::cpUrl($path));
    }
}
