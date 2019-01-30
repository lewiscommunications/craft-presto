<?php

namespace lewiscom\presto\controllers;

use Craft;
use craft\web\Controller;
use lewiscom\presto\Presto;
use craft\helpers\UrlHelper;

class GeneralSettingsController extends Controller
{
    public function actionIndex()
    {
        return $this->renderTemplate('presto/settings/general', [
            'settings' => Presto::$plugin->settings,
        ]);
    }
}
