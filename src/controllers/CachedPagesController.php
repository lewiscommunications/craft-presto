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
        return $this->renderTemplate('presto/cachedPages', [
            'cachedPages' => 'test',
        ]);
    }
}
