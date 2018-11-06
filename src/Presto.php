<?php

namespace lewiscom\presto;

use Craft;
use yii\base\Event;
use craft\base\Plugin;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Structures;
use lewiscom\presto\models\Settings;
use craft\events\RegisterUrlRulesEvent;
use lewiscom\presto\widgets\PrestoWidget;
use lewiscom\presto\services\CacheService;
use craft\web\twig\variables\CraftVariable;
use lewiscom\presto\services\CrawlerService;
use lewiscom\presto\variables\PrestoVariable;
use craft\events\RegisterComponentTypesEvent;
use lewiscom\presto\services\CachedPagesService;
use lewiscom\presto\services\EventHandlerService;
use craft\console\Application as ConsoleApplication;

/**
 * Class Presto
 *
 * @package lewiscom\presto
 * @property CacheService $cacheService
 * @property EventHandlerService $eventHandlerService
 * @property CrawlerService $crawlerService
 * @property CachePagesService $cachePagesService
 * @property Settings $settings
 * @method Settings getSettings()
 */
class Presto extends Plugin
{
    /**
     * Is triggered before generating the static cache file
     */
    const EVENT_BEFORE_GENERATE_CACHE_ITEM = 'beforeGenerateCacheItem';

    /**
     * Is triggered after generating the static cache file
     */
    const EVENT_AFTER_GENERATE_CACHE_ITEM = 'afterGenerateCacheItem';

    /**
     * Is triggred before cache items are purged
     */
    const EVENT_BEFORE_PURGE_CACHE = 'beforePurgeCache';

    /**
     * Is triggred after cache items are purged
     */
    const EVENT_AFTER_PURGE_CACHE = 'afterPurgeCache';

    /**
     * Is triggred before the entire cache is purged
     */
    const EVENT_BEFORE_PURGE_CACHE_ALL = 'beforePurgeCacheAll';

    /**
     * Is triggred after the entire cache is purged
     */
    const EVENT_AFTER_PURGE_CACHE_ALL = 'afterPurgeCacheAll';

    /**
     * Static property that is an instance of this plugin class so that it can
     * be accessed via Presto::$plugin
     *
     * @var Presto
     */
    public static $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its
     * schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * Initialize plugin
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'lewiscom\presto\console\controllers';
        }

        // Set the services on the instance, so you can access them by
        // $this->{serviceName}
        $this->setComponents([
            'cacheService' => CacheService::class,
            'eventHandlerService' => EventHandlerService::class,
            'crawlerService' => CrawlerService::class,
            'cachedPagesService' => CachedPagesService::class,
        ]);

        // Register everything
        $this->registerEvents();
        $this->registerVariables();
        $this->registerRoutes();
        $this->registerWidgets();

        Craft::info(
            Craft::t(
                'presto',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @return mixed|\yii\web\Response
     */
    public function getSettingsResponse()
    {
        return Craft::$app->controller->redirect(
            UrlHelper::cpUrl('presto/settings/general')
        );
    }

    /**
     * Register events
     */
    private function registerEvents()
    {
        // After an element is saved
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            [$this->eventHandlerService, 'handleAfterSaveElementEvent']
        );

        // Before an element is saved
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            [$this->eventHandlerService, 'handleBeforeSaveElementEvent']
        );

        // Before an element is deleted
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            [$this->eventHandlerService, 'handleBeforeDeleteElementEvent']
        );

        // Before an action is performed
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_PERFORM_ACTION,
            [$this->eventHandlerService, 'handleBeforePerformActionEvent']
        );

        // Before an element is moved
        Event::on(
            Structures::class,
            Structures::EVENT_BEFORE_MOVE_ELEMENT,
            [$this->eventHandlerService, 'handleBeforeMoveElementEvent']
        );

        Event::on(
            self::class,
            self::EVENT_AFTER_GENERATE_CACHE_ITEM,
            [
                $this->eventHandlerService,
                'handleAfterGenerateCacheItemEvent',
            ]
        );
    }

    /**
     * Register widgets
     */
    private function registerWidgets()
    {
        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = PrestoWidget::class;
            }
        );
    }

    /**
     * Register routes
     */
    private function registerRoutes()
    {
        // Register CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['presto/settings/general'] = 'presto/general-settings';
                $event->rules['presto/settings/cache-warming'] = 'presto/cache-warming-settings';
                $event->rules['presto/cachedPages'] = 'presto/cached-pages';
            }
        );
    }

    /**
     * Register variables
     */
    private function registerVariables()
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('presto', PrestoVariable::class);
            }
        );
    }
}
