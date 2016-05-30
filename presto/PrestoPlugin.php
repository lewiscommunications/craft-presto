<?php
namespace Craft;

class PrestoPlugin extends BasePlugin
{
	private $name = 'Presto';
	private $version = '0.3.0';
	private $description = 'Static file extension for the native Craft cache.';
	private $flash;

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
		}
	}

	/**
	 * Process new elements
	 *
	 * @param Event $event
	 */
	public function saveElement(Event $event)
	{
		$element = $event->params['element'];

		if ($event->params['isNewElement']) {
			$paths = craft()->presto->getPaths($element);
		} elseif ($this->flash) {
			$paths = $this->flash['paths'];

			// Process altered element paths
			if ($this->flash['uri'] !== $element->uri) {
				craft()->presto->purgeCache(array(
					'paths' => array(
						$this->flash['uri']
					)
				));

				if ($element->uri) {
					$paths[] = $element->uri;
				}
			}
		}

		if ($paths) {
			craft()->presto->processPaths($paths);
		}
	}

	/**
	 * Process element actions
	 *
	 * @param Event $event
	 */
	public function beforePerformAction(Event $event)
	{
		$paths = craft()->presto->getPaths(
			$event->params['criteria']->ids()
		);

		craft()->presto->processPaths($paths);
	}

	/**
	 * Process updated elements
	 *
	 * @param Event $event
	 */
	public function beforeSaveElement(Event $event)
	{
		if (! $event->params['isNewElement'] && ! $this->flash) {
			$element = $event->params['element'];

			$this->flash = array(
				'paths' => craft()->presto->getPaths($element),
				'uri' => $element->uri
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
		$paths = craft()->presto->getPaths(
			$event->params['elementIds']
		);

		craft()->presto->processPaths($paths);
	}
}