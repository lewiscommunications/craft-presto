<?php

namespace lewiscom\presto\widgets;

use Craft;
use craft\base\Widget;
use lewiscom\presto\Presto;
use lewiscom\presto\assetbundles\widget\WidgetAsset;

class PrestoWidget extends Widget
{
    public $message;

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        return Craft::t('presto', 'Presto');
    }

    /**
     * Returns the path to the widget’s SVG icon.
     *
     * @return string|null The path to the widget’s SVG icon
     */
    public static function iconPath()
    {
        return Craft::getAlias("@lewiscom/presto/icon.svg");
    }

    /**
     * Returns the widget’s maximum colspan.
     *
     * @return int|null The widget’s maximum colspan, if it has one
     */
    public static function maxColspan()
    {
        return 1;
    }

    /**
     * @return null|string|void
     */
    public function getSettingsHtml()
    {
        // TODO
        //return Craft::$app->getView()->renderTemplate(
        //    'presto/_components/widgets/settings',
        //    [
        //        'widget' => $this
        //    ]
        //);
    }

    /**
     * Returns widget HTML
     *
     * @return false|string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getBodyHtml()
    {
        Craft::$app->getView()->registerAssetBundle(WidgetAsset::class);


        return Craft::$app->getView()->renderTemplate(
            'presto/_components/widgets/body',
            [
                'cacheCount' => Presto::$plugin
                    ->cacheService
                    ->getStaticCacheFileCount(),
            ]
        );
    }
}
