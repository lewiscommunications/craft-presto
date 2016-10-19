<?php
namespace Craft;

class PrestoPlugin extends BasePlugin
{
	private $name = 'Presto';
	private $version = '0.5.0';
	private $description = 'Static file extension for the native Craft cache.';
	private $caches;

	public function getName()
	{
		return $this->name;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function getSchemaVersion()
	{
		return '1.0.0';
	}

	public function getDescription()
	{
		return Craft::t($this->description);
	}

	public function getDeveloper()
	{
		return 'Caddis';
	}

	public function getDeveloperUrl()
	{
		return 'https://www.caddis.co';
	}

	public function getDocumentationUrl()
	{
		return 'https://github.com/caddis/craft-presto';
	}

	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/caddis/craft-presto/master/releases.json';
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('presto/settings', array(
			'settings' => $this->getSettings()
		));
	}

	protected function defineSettings()
	{
		return array(
			'cachePath' => array(
				AttributeType::String,
				'default' => '/cache'
			)
		);
	}

	public function registerCachePaths()
	{
		$cachePath = craft()->config->get('rootPath', 'presto') .
			$this->getSettings()->cachePath;

		return array(
			$cachePath => $this->name . ' ' . Craft::t('caches')
		);
	}

	/**
	 * Bind element actions to Presto events
	 */
	public function init()
	{
		if (craft()->request->isCpRequest()) {
			craft()->on('elements.saveElement', array($this, 'saveElement'));
			craft()->on('elements.beforePerformAction', array($this, 'beforePerformAction'));
			craft()->on('elements.beforeSaveElement', array($this, 'beforeSaveElement'));
			craft()->on('elements.beforeDeleteElements', array($this, 'beforeDeleteElements'));
			craft()->on('structures.beforeMoveElement', array($this, 'beforeMoveElement'));
		}
	}

	/**
	 * Process element before saving
	 *
	 * @param Event $event
	 */
	public function beforeSaveElement(Event $event)
	{
		if (! $event->params['isNewElement'] && ! $this->caches) {
			$this->caches = craft()->presto->getRelatedTemplateCaches(
				$event->params['element']->id
			);
		}
	}

	/**
	 * Process element on save
	 *
	 * @param Event $event
	 */
	public function saveElement(Event $event)
	{
		if ($event->params['isNewElement']) {
			// If a new element is saved, bust the entire cache
			craft()->templateCache->deleteAllCaches();
			craft()->presto->purgeEntireCache();
		} elseif ($this->caches) {
			// Otherwise, target specific caches
			craft()->presto->purgeCache(
				craft()->presto->formatPaths($this->caches)
			);
		}
	}

	/**
	 * Process deleted elements
	 *
	 * @param Event $event
	 */
	public function beforeDeleteElements(Event $event)
	{
		$caches = craft()->presto->getRelatedTemplateCaches(
			$event->params['elementIds']
		);

		craft()->presto->purgeCache(craft()->presto->formatPaths($caches));
	}

	/**
	 * Process batch element actions
	 *
	 * @param Event $event
	 */
	public function beforePerformAction(Event $event)
	{
		if (! $event->params['action']->isDestructive()) {
			$caches = craft()->presto->getRelatedTemplateCaches(
				$event->params['criteria']->ids()
			);

			if (count($caches)) {
				// If there are caches to bust, target them specifically
				craft()->presto->purgeCache(craft()->presto->formatPaths($caches));
			} else {
				// Otherwise, clear the entire cache since the new/enabled
				// elements won't be part of the existing cache
				craft()->templateCache->deleteAllCaches();
				craft()->presto->purgeEntireCache();
			}
		}
	}

	/**
	 * Process structure reordering
	 *
	 * @param Event $event
	 */
	public function beforeMoveElement(Event $event)
	{
		$caches = craft()->presto->getRelatedTemplateCaches(
			$event->params['element']->id
		);

		craft()->presto->purgeCache(craft()->presto->formatPaths($caches));
	}
}