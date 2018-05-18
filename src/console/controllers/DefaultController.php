<?php

namespace lewiscom\presto\console\controllers;

use Craft;
use craft\helpers\FileHelper;
use lewiscom\presto\Presto;
use yii\console\Controller;

class DefaultController extends Controller
{
    public $prestoService;

    /**
     * @throws \yii\base\ErrorException
     */
    public function actionCheck()
    {
        $this->prestoService = Presto::$plugin->prestoService;

        $this->prestoService->updateRootPath(
            Presto::$plugin->settings->rootPath
        );

        // Does the purge log file exist?
        if (! file_exists($this->getUpdatePath())) {
            FileHelper::writeToFile($this->getUpdatePath(), '');
        }

        $lastUpdated = $this->getUpdateTime();
        $this->writeUpdateTime();

        if (! $lastUpdated) {
            $this->prestoService->purgeEntireCache();
        } else {
            $lastUpdated = $this->prestoService->getDateTime($lastUpdated);

            $paths = $this->prestoService->getPurgeEvents($lastUpdated);

            if (count($paths)) {
                if ($paths[0] === 'all') {
                    $this->prestoService->purgeEntireCache();
                } else {
                    $this->prestoService->purgeCache($paths);
                }
            }
        }
    }

    /**
     * Returns the string value for when the check was last run
     *
     * @return array|bool|string
     */
    private function getUpdateTime()
    {
        return file_get_contents(
            $this->getUpdatePath()
        );
    }

    /**
     * Returns the path to the file holding the last update check
     *
     * @return string
     */
    private function getUpdatePath()
    {
        return Craft::$app->path->getRuntimePath() . '/prestoPurgeEvents.txt';
    }

    /**
     * Updates last update check to the current time, formatted to be equivalent to
     * DateTimes stored in the database
     *
     * @throws \yii\base\ErrorException
     */
    private function writeUpdateTime()
    {
        FileHelper::writeToFile(
            $this->getUpdatePath(),
            $this->prestoService->getDateTime()
        );
    }
}
