<?php

namespace lewiscom\presto\console\controllers;


use Craft;
use yii\helpers\Console;
use yii\console\Controller;
use lewiscom\presto\Presto;

class DefaultController extends Controller
{
    // TODO: Add cron

    public function actionClearCache()
    {
        Presto::$plugin->cacheService->triggerPurge(true);
    }

    public function actionWarmCache()
    {
        Presto::$plugin->crawlerService->crawl(
            Craft::getAlias(Presto::$plugin->getSettings()->sitemapIndex, false)
        );
    }
}
