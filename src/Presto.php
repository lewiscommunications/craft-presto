<?php

namespace lewiscom\presto;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\events\ElementActionEvent;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\events\PluginEvent;
use craft\services\Elements;
use craft\services\Plugins;
use craft\services\Structures;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use lewiscom\presto\models\Settings;
use lewiscom\presto\variables\PrestoVariable;
use yii\base\Event;

class Presto extends Plugin
{
    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Presto::$plugin
     *
     * @var Presto
     */
    public static $plugin;

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '2.0.0';

    /**
     * @var array
     */
    private $caches;

    /**
     * Init
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'lewiscom\presto\console\controllers';
        }

        $this->registerEvents();

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
     * Save default settings
     *
     * @param PluginEvent $event
     */
    public function handleAfterInstallEvent(PluginEvent $event)
    {
        if ($event->plugin === $this) {
            Craft::$app->plugins->savePluginSettings(
                $this,
                [
                    'cachePath' => '/cache',
                    'rootPath' => $_SERVER['DOCUMENT_ROOT'],
                    'purgeMethod' => 'immediate',
                ]
            );
        }
    }

    /**
     * Process element on save
     *
     * @param ElementEvent $event
     */
    public function handleSaveElementEvent(ElementEvent $event)
    {
        $this->triggerPurge($event->isNew);
    }

    /**
     * Process batch element actions
     *
     * @param ElementActionEvent $event
     */
    public function handleBeforePerformActionEvent(ElementActionEvent $event)
    {
        if (! $event->action->isDestructive()) {
            $caches = $this->prestoService->getRelatedTemplateCaches(
                $event->criteria->ids()
            );

            if (count($caches)) {
                $this->triggerPurge(false, $caches);
            } else {
                $this->triggerPurge(true);
            }
        }
    }

    /**
     * Process element before saving
     *
     * @param ElementEvent $event
     */
    public function handleBeforeSaveElementEvent(ElementEvent $event)
    {
        if (! $event->isNew && ! $this->caches) {
            $this->caches = $this->prestoService->getRelatedTemplateCaches(
                $event->element->id
            );
        }
    }

    /**
     * Process deleted elements
     *
     * @param ElementEvent $event
     */
    public function handleBeforeDeleteElementEvent(ElementEvent $event)
    {
        $caches = $this->prestoService->getRelatedTemplateCaches(
            $event->element->id
        );

        $this->triggerPurge(false, $caches);
    }

    /**
     * Process structure reordering
     *
     * @param MoveElementEvent $event
     */
    public function handleBeforeMoveElementEvent(MoveElementEvent $event)
    {
        $caches = $this->prestoService->getRelatedTemplateCaches(
            $event->element->id
        );

        $this->triggerPurge(false, $caches);
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
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'presto/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     * Register all necessary events
     */
    private function registerEvents()
    {
        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'presto/default/do-something';
            }
        );

        // Save default settings
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            [$this, 'handleAfterInstallEvent']
        );

        // After an element is saved
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            [$this, 'handleSaveElementEvent']
        );

        // After an action is performed
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_PERFORM_ACTION,
            [$this, 'handleBeforePerformActionEvent']
        );

        // Before an element is saved
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_SAVE_ELEMENT,
            [$this, 'handleBeforeSaveElementEvent']
        );

        // Before an element is deleted
        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            [$this, 'handleBeforeDeleteElementEvent']
        );

        // Before an element is moved
        Event::on(
            Structures::class,
            Structures::EVENT_BEFORE_MOVE_ELEMENT,
            [$this, 'handleBeforeMoveElementEvent']
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                $event->sender->set('presto', PrestoVariable::class);
            }
        );
    }

    /**
     * Triggers purging the Presto cache, checking whether to trigger immediate or cron
     *
     * @param bool $all
     * @param array $caches
     */
    private function triggerPurge($all = false, $caches = [])
    {
        $immediate = $this->getSettings()->purgeMethod === 'immediate';

        if ($all) {
            if ($immediate) {
                $this->prestoService->purgeEntireCache();
            } else {
                $this->prestoService->storePurgeAllEvent();
            }
        } else if (count($caches) || $this->caches) {
            $caches = count($caches) ? $caches : $this->caches;

            if ($immediate) {
                $this->prestoService->purgeCache(
                    $this->prestoService->formatPaths($caches)
                );
            } else {
                $this->prestoService->storePurgeEvent(
                    $this->prestoService->formatPaths($caches)
                );
            }
        }
    }
}
