<?php

namespace lewiscom\presto\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use lewiscom\presto\Presto;

class DefaultController extends Controller
{
    /**
     * Purge cache
     *
     * @return \yii\web\Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionPurgeCache()
    {
        $this->requireAdmin();

        $prestoService = Presto::getInstance()->prestoService;
        $cron = Presto::getInstance()->settings->purgeMethod === 'cron';

        if ($cron) {
            $prestoService->storePurgeAllEvent();
		} else {
			// Clear static cache
			$prestoService->purgeEntireCache();

			// Clear db template cache
			Craft::$app->templateCaches->deleteAllCaches();
		}

		Craft::$app->session->setNotice(
		    Craft::t('presto', $cron ? 'Cache purge scheduled.' : 'Cache purged.')
        );

        return $this->redirect(UrlHelper::cpUrl('settings/plugins/presto'));
    }
}
