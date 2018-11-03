<?php

namespace lewiscom\presto\jobs;

use Craft;
use GuzzleHttp\Client;
use craft\queue\BaseJob;

class WarmCacheTask extends BaseJob
{
    /**
     * Some attribute
     *
     * @var mixed
     */
    public $element;

    /**
     * @param \craft\queue\QueueInterface|\yii\queue\Queue $queue
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute($queue)
    {
        $client = new Client();
        $client->request(
            'GET',
            $this->element->url,
            [
                'verify' => false,
                'exceptions' => false,
            ]
        );
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
