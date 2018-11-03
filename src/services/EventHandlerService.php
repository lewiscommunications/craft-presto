<?php

namespace lewiscom\presto\services;

use Craft;
use craft\base\Component;
use lewiscom\presto\jobs\WarmCacheTask;
use lewiscom\presto\Presto;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\events\ElementActionEvent;

class EventHandlerService extends Component
{
    /**
     * @var mixed
     */
    public $settings;

    public $cacheService;

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
        if (! $event->isNew) {
            $this->cacheService->triggerPurge();
        }

        if ($this->settings->warmCache) {
            Craft::$app
                ->getQueue()
                ->push(new WarmCacheTask([
                    'element' => $event->element,
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
        if (! $event->isNew && ! $this->cacheService->caches) {
            $this->cacheService->setCaches([$event->element->id]);
        }
    }

    /**
     * Process deleted elements
     *
     * @param ElementEvent $event
     */
    public function handleBeforeDeleteElementEvent(ElementEvent $event)
    {
        $caches = $this->cacheService->getRelatedTemplateCaches([
            $event->element->id
        ]);

        $this->cacheService->triggerPurge(false, $caches);
    }

    /**
     * Process batch element actions
     *
     * @param ElementActionEvent $event
     */
    public function handleBeforePerformActionEvent(ElementActionEvent $event)
    {
        if (! $event->action->isDestructive()) {
            $caches = $this->cacheService->getRelatedTemplateCaches(
                $event->criteria->ids()
            );

            if (count($caches)) {
                $this->cacheService->triggerPurge(false, $caches);
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
        $caches = $this->cacheService->getRelatedTemplateCaches([
            $event->element->id
        ]);

        $this->cacheService->triggerPurge(false, $caches);
    }
}