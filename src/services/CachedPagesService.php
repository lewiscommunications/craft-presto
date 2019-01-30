<?php

namespace lewiscom\presto\services;

use Craft;
use craft\db\Query;
use craft\base\Component;
use lewiscom\presto\Presto;
use lewiscom\presto\utils\Paginator;

class CachedPagesService extends Component
{
    public $table = '{{%presto_cache_item_record}}';

    /**
     * @param $page
     * @param $search
     * @return array
     */
    public function getCachedPages($page, $search)
    {
        $pageSize = 15;
        $offset = $page > 1 ? ($page - 1) * $pageSize : 0;

        $query = (new Query())
            ->select('*')
            ->from(["$this->table AS cachedPages"])
            ->join(
                'JOIN',
                '{{%templatecaches}} as caches',
                'caches.cacheKey = cachedPages.cacheKey'
            );

        if ($search) {
            $query->where(['like', 'cachedPages.cacheKey', $search]);
        }

        $countQuery = clone $query;
        $count = $countQuery->count();

        $test = clone $query;

        $items = $query
            ->offset($offset)
            ->limit($pageSize)
            ->all();

        $totalPages = (int) ceil( $count / $pageSize);

        foreach ($items as &$item) {
            $item['age'] = $this->getTimeDiff(
                $item['dateCreated'],
                false,
                2,
                false);
        }

        return [
            'items' => $items,
            'paginator' => new Paginator($page, $totalPages),
        ];
    }

    /**
     * Calculates the difference between two dates and returns the relative
     * time in a human readable format
     *
     * @param $from
     * @param bool $to
     * @param int $precision
     * @param string $suffix
     * @return string
     */
    private function getTimeDiff($from, $to = false, $precision = 2, $suffix = 'ago')
    {
        if (! $to) {
            $to = new \DateTime(gmdate('Y-m-d H:i:s'));
        }

        $diff = (new \DateTime($from))->diff($to);
        $parts = [];

        $map = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        foreach ($diff as $key => $value) {
            if (isset($map[$key]) && $value) {
                $parts[] = $value . ' ' . $map[$key] . ($value > 1 ? 's' : '');
            }
        }

        if (count($parts) < $precision) {
            $precision = 0;
        } else {
            $precision = count($parts) - $precision;
        }

        $reversed = array_reverse($parts);

        return implode(
            ', ',
            array_reverse(array_slice($reversed, $precision))
        ) . ($suffix ? ' ' . $suffix : '');
    }
}