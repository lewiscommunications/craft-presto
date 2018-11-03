<?php

namespace lewiscom\presto\controllers;

use function array_unshift;
use craft\helpers\UrlHelper;
use lewiscom\presto\Presto;

use Craft;
use craft\web\Controller;

class DefaultController extends Controller
{
    /**
     * Purge the cache
     *
     * @return \yii\web\Response
     * @throws \yii\base\ErrorException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionPurgeCache()
    {
        $this->requireAdmin();

        $cacheService = Presto::$plugin->cacheService;
        $cron = Presto::$plugin->getSettings()->purgeMethod === 'cron';

        if ($cron) {
            // TODO:
            //$cacheService->storePurgeAllEvent();
		} else {
            // Clear db template cache
			Craft::$app->templateCaches->deleteAllCaches();

			// Clear static cache
			$cacheService->purgeEntireCache();
		}

		Craft::$app->session->setNotice(
		    Craft::t('presto', $cron ? 'Cache purge scheduled.' : 'Cache purged.')
        );

        // Figure out if request came from dashboard or widget
        $referrer = explode('/', Craft::$app->request->referrer);
        $path = end($referrer) === 'dashboard'
            ? '/dashboard'
            : 'settings/plugins/presto';

        return $this->redirect(UrlHelper::cpUrl($path));
    }
}
