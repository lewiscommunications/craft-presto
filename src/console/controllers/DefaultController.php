<?php

namespace lewiscom\presto\console\controllers;


use Craft;
use yii\helpers\Console;
use yii\console\Controller;
use lewiscom\presto\Presto;

class DefaultController extends Controller
{
    // TODO: Add cron

    /**
     * Clear the Craft and static template cache
     *
     * @throws \yii\base\ErrorException
     */
    public function actionClearCache()
    {
        Presto::$plugin->cacheService->triggerPurge(true);

        echo "Cache has been purged." . PHP_EOL;
    }

    /**
     * Warm the cache
     */
    public function actionWarmCache()
    {
        Presto::$plugin->crawlerService->crawl(
            Craft::getAlias(Presto::$plugin->getSettings()->sitemapIndex, false)
        );

        echo "Cache warming has started." . PHP_EOL;
    }
}
