<?php
namespace Craft;

class PrestoService extends BaseApplicationComponent
{
	private $settings;
	private $rootPath;

	public function __construct()
	{
		$this->settings = craft()->plugins->getPlugin('presto')->getSettings();
		$this->rootPath = craft()->config->get('rootPath', 'presto');
	}

	/**
	 * @param $dateTime
	 * @return array
	 */
	public function getPurgeEvents($dateTime)
	{
		$paths = [];

		$events = Presto_PrestoCachePurgeRecord::model()
			->findAll('purgedAt >= :dateTime', [
				':dateTime' => $dateTime->mySqlDateTime()
			]);

		foreach ($events as $event) {
			if ($event->paths === 'all') {
				return [
					'all'
				];
			}

			$paths = array_merge($paths, unserialize($event->paths));
		}

		sort($paths);

		return $paths;
	}

	/**
	 * Record specific cache paths that need to be busted
	 *
	 * @param array $paths
	 */
	public function storePurgeEvent($paths = [])
	{
		if (count($paths)) {
			$this->storeEvent(serialize($paths));
		}
	}

	/**
	 * Record the need to bust the entire cache
	 */
	public function storePurgeAllEvent()
	{
		$this->storeEvent('all');
	}

	/**
	 * Store recorded events for the static cache
	 *
	 * @param $paths
	 */
	private function storeEvent($paths)
	{
		$event = new Presto_PrestoCachePurgeRecord;

		$event->setAttributes([
			'purgedAt' => $this->getDateTime(),
			'paths' => $paths
		]);

		$event->save();
	}

	/**
	 * Get date time
	 *
	 * @param null $setStamp
	 * @return DateTime
	 */
	public function getDateTime($setStamp = null)
	{
		return new DateTime($setStamp ? $setStamp : 'now', new \DateTimeZone(craft()->getTimeZone()));
	}

	/**
	 * Update root path
	 *
	 * @param string $path
	 */
	public function updateRootPath($path)
	{
		if (IOHelper::folderExists($path)) {
			$this->rootPath = $path;
		}
	}

	/**
	 * Purge cached files by path
	 *
	 * @param array $paths
	 */
	public function purgeCache($paths = [])
	{
		if (count($paths)) {
			$purgedUrls = [];

			foreach ($paths as $path) {
				$url = explode('|', $path, 2);

				if (count($url) < 2) {
					continue;
				}

				$targetPath = IOHelper::folderExists(
					$this->normalizePath(implode('/', [
						$this->rootPath,
						$this->settings->cachePath,
						$url[0],
						'presto',
						str_replace('home', '', $url[1])
					]))
				);

				$targetFile = IOHelper::fileExists($targetPath . '/index.html');

				if ($targetFile) {
					@unlink($targetFile);
				}

				if ($targetPath) {
					@rmdir($targetPath);
				}

				if ($targetFile || $targetPath) {
					$purgedUrls[] = UrlHelper::getSiteUrl(str_replace('home', '', $url[1]));
				}
			}

			$this->onPurgeCache(new Event($this, [
				'all' => false,
				'urls' => $purgedUrls,
			]));
		}
	}

	/**
	 * Purge all cached files
	 */
	public function purgeEntireCache()
	{
		$cachePath = IOHelper::folderExists(
			$this->rootPath .
			$this->settings->cachePath
		);

		if ($cachePath) {
			IOHelper::clearFolder($cachePath);

			$this->onPurgeCache(new Event($this, [
				'all' => true,
				'urls' => [],
			]));
		}
	}

	/**
	 * Write the HTML output to a static cache file
	 *
	 * @param string $host
	 * @param string $path
	 * @param string $html
	 * @param array $config
	 */
	public function writeCache($host, $path, $html, $config = [])
	{
		if (! isset($config['static']) || $config['static'] !== false) {
			$pathSegments = [
				$this->rootPath,
				$this->settings->cachePath,
				$host,
				'presto'
			];

			if (isset($config['group'])) {
				$pathSegments[] = $config['group'];
			}

			$pathSegments[] = $path;

			$targetPath = $this->normalizePath(implode('/', $pathSegments));

			$pathInfo = pathinfo($targetPath);
			$extension = isset($pathInfo['extension']) ?
				$pathInfo['extension'] : 'html';

			$targetFile = $targetPath . '/index.' . $extension;

			IOHelper::writeToFile($targetFile, trim($html));
		}
	}

	/**
	 * Determine if the current request is cacheable
	 *
	 * @return bool
	 */
	public function isCacheable()
	{
		return http_response_code() === 200 &&
			! craft()->request->isLivePreview() &&
			! craft()->request->isPostRequest();
	}

	/**
	 * Generate cacheKey based on the host and path
	 *
	 * @param array $keySegments {
	 *		@var string $host
	 *		@var string $path
	 * 		@var string $group (optional)
	 * }
	 * @return string
	 */
	public function generateKey($keySegments)
	{
		$group = isset($keySegments['group']) ? $keySegments['group'] . '/' : '';
		$path = $keySegments['path'] ? $keySegments['path'] : 'home';

		return $keySegments['host'] . '|' . $group . $path;
	}

	/**
	 * Get template caches by elementId
	 *
	 * @param array|string $elementIds
	 * @return array
	 */
	public function getRelatedTemplateCaches($elementIds)
	{
		return craft()->db->createCommand()
			->selectDistinct('cacheKey')
			->from('templatecaches as caches')
			->join(
				'templatecacheelements as elements',
				'caches.id = elements.cacheId'
			)
			->where([
				'in',
				'elements.elementId',
				$elementIds
			])
			->queryAll();
	}

	/**
	 * Format cacheKey arrays into an array of paths
	 *
	 * @param $caches
	 * @return array
	 */
	public function formatPaths($caches)
	{
		$paths = [];

		foreach ($caches as $cache) {
			$paths[] = $cache['cacheKey'];
		}

		return $paths;
	}

	/**
	 * Fires an 'onPurgeCache' event.
	 *
	 * @param Event $event
	 * @return null
	 */
	public function onPurgeCache(Event $event)
	{
		$this->raiseEvent('onPurgeCache', $event);
	}

	/**
	 * De-duplicate slashes in a file system path
	 *
	 * @param string $path
	 * @return string
	 */
	private function normalizePath($path)
	{
		return rtrim(preg_replace('~/+~', '/', $path), '/');
	}
}
