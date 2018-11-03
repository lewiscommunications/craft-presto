<?php

namespace lewiscom\presto\jobs;

use Craft;
use GuzzleHttp\Client;
use craft\queue\BaseJob;

class WarmCacheTask extends BaseJob
{
    /**
     * @var array
     */
    public $urls = [];

    /**
     * @param \craft\queue\QueueInterface|\yii\queue\Queue $queue
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute($queue)
    {
        $client = new Client();

        foreach ($this->urls as $url) {
            $client->request(
                'GET',
                $url,
                [
                    'verify' => false,
                    'exceptions' => false,
                ]
            );
        }
    }

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('presto', 'Warming Cache');
    }
}
