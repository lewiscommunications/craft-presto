<?php

namespace lewiscom\presto\assetbundles\widget;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WidgetAsset extends AssetBundle
{
     /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@lewiscom/presto/assetbundles/widget/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/widget.js',
        ];

        $this->css = [
            'css/widget.css',
        ];

        parent::init();
    }
}
