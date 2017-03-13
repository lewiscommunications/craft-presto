<?php
namespace Craft;

class PrestoPlugin extends BasePlugin
{
	private $name = 'Presto';
	private $version = '0.6.0';
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
		return '1.1.0';
	}

	public function getDescription()
	{
		return Craft::t($this->description);
	}

	public function getDeveloper()
	{
		return 'Lewis Communications';
	}

	public function getDeveloperUrl()
	{
		return 'http://www.lewiscommunications.com';
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

	public function registerCpRoutes()
	{
		// Point the purge action to our presto controller
		return array(
			'settings/plugins/presto/purge' => [
				'action' => 'presto/purgeCache'
			]
		);
	}

	protected function defineSettings()
	{
		return array(
			'rootPath' => array(
				AttributeType::String,
				'default' => craft()->config->get('rootPath', 'presto')
			),
			'cachePath' => array(
				AttributeType::String,
				'default' => '/cache'
			)
		);
	}

	private function updateSettings()
	{
		craft()->plugins->savePluginSettings(
			$this,
			[
				'rootPath' => craft()->config->get('rootPath', 'presto')
			]
		);
	}

	public function registerCachePaths()
	{
		// Don't add the Presto path if we're purging via the cron
		if (craft()->config->get('purgeMethod', 'presto') === 'immediate') {
			$cachePath = craft()->config->get('rootPath', 'presto') .
				$this->getSettings()->cachePath;

			return array(
				$cachePath => $this->name . ' ' . Craft::t('caches')
			);
		}
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
	 * Ensure rootPath gets stored with the plugin settings
	 */
	public function onAfterInstall()
	{
		$this->updateSettings();
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
	 * Triggers purging the Presto cache, checking whether to trigger immediate or cron
	 *
	 * @param bool $all
	 * @param array $caches
	 */
	private function triggerPurge($all = false, $caches = [])
	{
		$immediate = craft()->config->get('purgeMethod', 'presto') === 'immediate';

		if ($all) {
			craft()->templateCache->deleteAllCaches();

			if ($immediate) {
				craft()->presto->purgeEntireCache();
			} else {
				craft()->presto->storePurgeAllEvent();
			}
		} elseif (count($caches) || $this->caches) {
			$caches = count($caches) ? $caches : $this->caches;

			if ($immediate) {
				craft()->presto->purgeCache(
					craft()->presto->formatPaths($caches)
				);
			} else {
				craft()->presto->storePurgeEvent(
					craft()->presto->formatPaths($caches)
				);
			}
		}
	}

	/**
	 * Process element on save
	 *
	 * @param Event $event
	 */
	public function saveElement(Event $event)
	{
		// If a new element is saved, bust the entire cache
		$this->triggerPurge(
			$event->params['isNewElement']
		);
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

		$this->triggerPurge(false, $caches);
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
				$this->triggerPurge(false, $caches);
			} else {
				$this->triggerPurge(true);
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

		$this->triggerPurge(false, $caches);
	}
}