<?php

namespace lewiscom\presto\jobs;

use Craft;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use craft\queue\BaseJob;
use GuzzleHttp\Psr7\Request;
use lewiscom\presto\utils\Logger;

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
        $client = new Client([
            'verify' => false,
            'exceptions' => false,
        ]);

        $requestGenerator = function($urls) {
            foreach ($urls as $url) {
                yield new Request('GET', $url);
            }
        };

        $total = count($this->urls);

        $pool = new Pool($client, $requestGenerator($this->urls), [
            'concurrency' => 5,
            'fulfilled' => function($response, $index) use ($queue, $total) {
                $this->setProgress($queue, $index / $total);

                Logger::log(
                    '{url} - request successful.',
                    __METHOD__
                );
            },
            'rejected' => function() {
                Logger::log(
                    '{url} - request failed with status code {statusCode}.',
                    __METHOD__
                );
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();
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
