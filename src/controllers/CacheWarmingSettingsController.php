<?php

namespace lewiscom\presto\controllers;

use Craft;
use craft\web\Controller;
use lewiscom\presto\Presto;
use craft\helpers\UrlHelper;

class CacheWarmingSettingsController extends Controller
{
    public function actionIndex()
    {
        return $this->renderTemplate('presto/settings/warming', [
            'settings' => Presto::$plugin->settings,
        ]);
    }
}
