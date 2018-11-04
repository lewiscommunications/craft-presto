<?php

namespace lewiscom\presto\utils;

use Craft;

class Logger
{
    public static function log($message, $method, $data = [])
    {
        Craft::info(
            Craft::t(
                'presto',
                $message,
                $data
            ),
            $method
        );
    }
}