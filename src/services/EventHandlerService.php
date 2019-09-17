<?php

namespace lewiscom\presto\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use lewiscom\presto\events\CacheEvent;
use lewiscom\presto\Presto;
use craft\events\ElementEvent;
use craft\events\MoveElementEvent;
use craft\events\ElementActionEvent;
use lewiscom\presto\jobs\WarmCacheTask;
use lewiscom\presto\records\PrestoCacheItemRecord;

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

        // TODO: Should default to empty array
        if (! $this->settings->sections) {
            $this->settings->sections = [];
        }
    }

    /**
     * @param ElementEvent $event
     */
    public function handleAfterSaveElementEvent(ElementEvent $event)
    {
        if ($event->element instanceof Entry) {
            $all = $this->settings->sections === '*' ||
                in_array($event->element->sectionId, $this->settings->sections);
            $this->cacheService->triggerPurge($all);

            if ($this->settings->warmCache) {
                Craft::$app
                    ->getQueue()
                    ->push(new WarmCacheTask([
                        'urls' => $this->cacheService->getUrlsFromCacheKeys(),
                    ]));
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
        $element = $event->element;

        $this->cacheService->setCaches([
            $element->id
        ]);

        if ($element instanceof Entry && $element->sectionId) {
            $all = in_array($element->sectionId, $this->settings->sections);
            $this->cacheService->triggerPurge($all);
        }
    }

    /**
     * Process batch element actions
     *
     * @param ElementActionEvent $event
     */
    public function handleBeforePerformActionEvent(ElementActionEvent $event)
    {
        if (! $event->action->isDestructive()) {
            $ids = $event->criteria->ids();
            $this->cacheService->setCaches($ids);

            $entries = Entry::findAll([
                'id' => $ids,
                'status' => ['live', 'expired', 'disabled', 'pending']
            ]);

            $sectionIds = array_map(function($entry) {
                return $entry->sectionId;
            }, $entries);

            $all = ! count(array_intersect(
                $sectionIds,
                $this->settings->sections ?? []
            ));

            if ($all) {
                $this->cacheService->triggerPurge($all);
            } else {
                if ($this->cacheService->hasCaches()) {
                    $this->cacheService->triggerPurge(false, $this->cacheService->caches);
                } else {
                    $this->cacheService->triggerPurge(true);
                }
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
        $element = $event->element;

        $this->cacheService->setCaches([
            $element->id
        ]);

        if ($element instanceof Entry && $element->sectionId) {
            $all = in_array($element->sectionId, $this->settings->sections);
            $this->cacheService->triggerPurge($all);
        }
    }

    /**
     * Save the cache record to the database
     *
     * @param CacheEvent $event
     * @throws \craft\errors\SiteNotFoundException
     */
    public function handleAfterGenerateCacheItemEvent(CacheEvent $event)
    {
        $record = new PrestoCacheItemRecord();
        $record->siteId = Craft::$app->sites->getCurrentSite()->id;
        $record->cacheKey = $event->cacheKey;
        $record->filePath = $event->filePath;
        $record->cacheGroup = $event->config['group'] ?? null;
        $record->url = implode('/', [$event->host, $event->path]);
        $record->save();
    }
}
