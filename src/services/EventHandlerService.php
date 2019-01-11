<?php

namespace lewiscom\presto\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use lewiscom\presto\Presto;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\events\ElementActionEvent;
use lewiscom\presto\jobs\WarmCacheTask;

class EventHandlerService extends Component
{
    /**
     * @var mixed
     */
    public $settings;

    /**
     * @var mixed
     */
    public $cacheService;

    /**
     * Initialize component
     */
    public function init()
    {
        parent::init();

        $this->settings = Presto::$plugin->getSettings();
        $this->cacheService = Presto::$plugin->cacheService;
    }

    /**
     * @param ElementEvent $event
     */
    public function handleAfterSaveElementEvent(ElementEvent $event)
    {
        $this->cacheService->triggerPurge();

        if ($this->settings->warmCache) {
            Craft::$app
                ->getQueue()
                ->push(new WarmCacheTask([
                    'urls' => $this->cacheService->getUrlsFromCacheKeys(),
                ]));
        }
    }

    /**
     * Process element before saving
     *
     * @param ElementEvent $event
     */
    public function handleBeforeSaveElementEvent(ElementEvent $event)
    {
        // Check if element is an entry before proceeding
        if ($event->element instanceof Entry) {
            if (! $event->isNew && ! $this->cacheService->caches) {
                $this->cacheService->setCaches([
                    $event->element->id
                ]);
            } else if ($event->isNew && ! $this->cacheService->caches) {
                $entries = Entry::find()->where([
                    'sectionId' => $event->element->sectionId
                ])->all();

                $caches = array_map(function($entry) {
                    return $entry->id;
                }, $entries);

                $this->cacheService->setCaches($caches);
            }
        }
    }

    /**
     * Process deleted elements
     *
     * @param ElementEvent $event
     */
    public function handleBeforeDeleteElementEvent(ElementEvent $event)
    {
        $this->cacheService->setCaches([
            $event->element->id
        ]);

        $this->cacheService->triggerPurge();
    }

    /**
     * Process batch element actions
     *
     * @param ElementActionEvent $event
     */
    public function handleBeforePerformActionEvent(ElementActionEvent $event)
    {
        if (! $event->action->isDestructive()) {
            $this->cacheService->setCaches($event->criteria->ids());

            if ($this->cacheService->hasCaches()) {
                $this->cacheService->triggerPurge(false, $this->cacheService->caches);
            } else {
                $this->cacheService->triggerPurge(true);
            }
        }
    }

    /**
     * Process structure reordering
     *
     * @param MoveElementEvent $event
     */
    public function handleBeforeMoveElementEvent(MoveElementEvent $event)
    {
        $this->cacheService->setCaches([
            $event->element->id
        ]);

        $this->cacheService->triggerPurge();
    }
}