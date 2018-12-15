<?php

namespace lewiscom\presto\controllers;

use Craft;
use craft\web\Controller;
use lewiscom\presto\Presto;
use craft\helpers\UrlHelper;

class CachedPagesController extends Controller
{
    public function actionIndex()
    {
        $page = Craft::$app->request->getQueryParam('page', 1);
        $cachedPages = Presto::$plugin
            ->cachedPagesService
            ->getCachedPages($page);

        return $this->renderTemplate('presto/cache', [
            'cache' => $cachedPages['items'],
            'paginate' => $cachedPages['paginator'],
        ]);
    }

    public function actionPurgeSelected()
    {
        $this->requirePostRequest();
        $cacheKeys = Craft::$app
            ->getRequest()
            ->getRequiredBodyParam('cacheKeys');

        // TODO: Possibly revist how we're sending cache keys to the triggerPurge
        // method.  Curent implementation requires us to make a multidimensial associative
        // array which is less than ideal.
        $keys = array_map(function($cacheKey) {
            return [
                'cacheKey' => $cacheKey
            ];
        }, $cacheKeys);

        Presto::$plugin->cacheService->triggerPurge(false, $keys);

        return $this->redirect(UrlHelper::cpUrl('presto/cache'));
    }
}
