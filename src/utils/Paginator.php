<?php

namespace lewiscom\presto\utils;

class Paginator
{
    public $currentPage;
    public $totalPages;
    public $nextPage;
    public $prevPage;
    public $prevUrl;
    public $nextUrl;

    public function __construct($page, $totalPages)
    {
        $this->params = \Craft::$app->request->getQueryParams();
        $this->currentPage = (int) $page;
        $this->totalPages = (int) $totalPages;
        $this->nextPage = (int) $this->currentPage === $this->totalPages ? null : $this->currentPage + 1;
        $this->prevPage = (int) $this->currentPage === 1 ? null : $page - 1;
        $this->nextUrl = $this->getNextUrls(1, true);
        $this->prevUrl = $this->getPrevUrls(1, true);
    }

    public function getNextUrls($pages, $one = false)
    {
        $end = $pages ? $this->currentPage + $pages : $this->totalPages;
        $range = $this->rangeUrls(($this->currentPage + 1), $end);

        return $one ? reset($range) : $range;
    }

    public function getPrevUrls($pages, $one = false)
    {
        $start = $pages ? $this->currentPage - $pages : 1;
        $range = $this->rangeUrls($start, ($this->currentPage - 1));

        return $one ? reset($range) : $range;
    }

    private function rangeUrls($start, $end)
    {
        $urls = [];

        if ($start < 1) {
            $start = 1;
        }

        if ($end > $this->totalPages) {
            $end = $this->totalPages;
        }

        for ($page = $start; $page <= $end; $page++) {
            $params = array_merge($this->params, [
                'page' => $page
            ]);

            $urls[$page] = '?' . http_build_query($params);
        }

        return $urls;
    }
}