<?php

namespace lewiscom\presto\models;

use Craft;
use craft\base\Model;
use lewiscom\presto\Presto;

class Settings extends Model
{
    /**
     * @var $rootPath
     */
    public $rootPath;

    /**
     * @var string
     */
    public $cachePath;

    /**
     * @var string
     */
    public $purgeMethod;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['rootPath', 'string'],
            ['cachePath', 'string'],
            ['purgeMethod', 'string'],
        ];
    }
}
