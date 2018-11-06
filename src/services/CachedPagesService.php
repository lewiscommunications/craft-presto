<?php

namespace lewiscom\presto\services;

use Craft;
use craft\db\Query;
use craft\base\Component;
use lewiscom\presto\Presto;
use lewiscom\presto\utils\Paginator;

class CachedPagesService extends Component
{
    public $table = '{{%presto_cache_record}}';

    public function getCachedPages($page)
    {
        $pageSize = 5;
        $offset = $page > 1 ? ($page - 1) * $pageSize : 0;

        $query = (new Query())
            ->select('*')
            ->from(["$this->table AS cachedPages"])
            ->join(
                'JOIN',
                '{{%templatecaches}} as caches',
                'caches.cacheKey = cachedPages.cacheKey'
            );

        $countQuery = clone $query;
        $count = $countQuery->count();

        $test = clone $query;

        $items = $query
            ->offset($offset)
            ->limit($pageSize)
            ->all();

        $totalPages = (int) ceil( $count / $pageSize);

        return [
            'items' => $items,
            'paginator' => new Paginator($page, $totalPages),
        ];
    }
}