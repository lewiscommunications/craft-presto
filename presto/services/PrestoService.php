<?php
namespace Craft;

class PrestoService extends BaseApplicationComponent
{
	private $settings;
	private $processPaths = array();

	public function __construct()
	{
		$this->settings = craft()->plugins->getPlugin('presto')->getSettings();
	}

	/**
	 * Get all cached paths related to element
	 *
	 * @param array $elements
	 * @return array
	 */
	public function getPaths($elements)
	{
		$elements = is_array($elements) ? $elements : array($elements);
		$cacheIds = array();
		$paths = array();

		foreach ($elements as $element) {
			if (is_string($element)) {
				$element = craft()->elements->getElementById($element);
			}

			$rows = $this->getCriteria($element->elementType);

			// Get potential matching caches based on template criteria
			foreach ($rows as $row) {
				$params = JsonHelper::decode($row['criteria']);
				$criteria = craft()->elements->getCriteria($row['type'], $params);
				$criteria->status = null;

				// Add the cache ID if the template includes matching elements
				if (in_array($element->id, $criteria->ids())) {
					$cacheIds[] = $row['cacheId'];
				}
			}

			// Get directly related cache element
			$cacheIds = array_merge($this->getCaches($element->id), $cacheIds);

			if ($element->uri) {
				$paths[] = $element->uri;
			}
		}

		return $paths + $this->queryCachePaths(array_unique($cacheIds));
	}

	/**
	 * Refresh cache for given paths
	 *
	 * @param array $paths
	 */
	public function processPaths($paths)
	{
		if (count($paths)) {
			$paths = array_values(array_unique($paths));
			$diff = array_diff($paths, $this->processPaths);

			if (count($diff)) {
				$this->processPaths = array_merge($this->processPaths, $diff);

				// Cancel existing Presto task
				if ($task = craft()->tasks->getNextPendingTask('Presto')) {
					craft()->tasks->deleteTaskById($task->id);
				}

				$this->purgeCache(array(
					'paths' => $diff
				));

				craft()->tasks->createTask('Presto', null, array(
					'paths' => $this->processPaths
				));
			}
		}
	}

	/**
	 * Purge files from the cache
	 *
	 * @param array $config
	 */
	public function purgeCache($config = array())
	{
		$expired = isset($config['expired']) ? $config['expired'] : false;
		$paths = isset($config['paths']) ? $config['paths'] : array('/');
		$recursive = isset($config['recursive']) ? $config['recursive'] : true;
		$warm = isset($config['warm']) ? $config['warm'] : false;

		$groups = craft()->config->get('groups', 'presto');
		$groups[] = '';

		$cachePaths = $this->getCachePaths($paths, $groups, $recursive);
		$cacheEntries = $this->getCacheEntries($cachePaths, $expired);

		// Filter out unexpired cached paths
		if ($expired) {
			$entryPaths = array_unique(array_column($cacheEntries, 'path'));
			$cachePaths = array_intersect($entryPaths, $cachePaths);
		}

		craft()->templateCache->deleteCacheById(
			array_unique(array_column($cacheEntries, 'id'))
		);

		// Remove matched cache files
		foreach ($groups as $group) {
			foreach ($cachePaths as $path) {
				$targetPath = $this->normalizePath(implode('/', array(
					craft()->config->get('rootPath', 'presto'),
					$this->settings->cachePath,
					$group,
					$path
				)));

				$targetFile = $targetPath . '/index.html';

				@unlink($targetFile);
				@rmdir($targetPath);
			}
		}

		if ($warm) {
			$task = craft()->tasks->createTask('Presto', null, array(
				'paths' => array_values(array_unique($cachePaths))
			));

			craft()->tasks->runTask($task);
		}
	}

	/**
	 * Write the HTML output to a static cache file
	 *
	 * @param string $path
	 * @param string $html
	 * @param array $config
	 */
	public function writeCache($path, $html, $config = array())
	{
		if (! isset($config['static']) || $config['static'] !== false) {
			$pathSegments = array(
				craft()->config->get('rootPath', 'presto'),
				$this->settings->cachePath
			);

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
	 * Send page request
	 *
	 * @param string $path
	 * @param array $headers
	 * @return string
	 */
	public function requestPage($path, $headers = array())
	{
		$client = new \Guzzle\Http\Client();

		$options = array(
			'exceptions' => false,
			'headers' => $headers,
			'verify' => false
		);

		return $client->get(craft()->getSiteUrl() . $path, null, $options)
			->send()
			->getBody();
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
	 * Generate native Craft cache key
	 *
	 * @param string $path
	 * @param mixed $fingerprint
	 * @return string
	 */
	public function generateKey($path, $fingerprint = false)
	{
		return md5(JsonHelper::encode(array(
			'fingerprint' => $fingerprint !== false ?
				$fingerprint :
				craft()->config->get('fingerprint', 'presto'),
			'path' => $path
		)));
	}

	/**
	 * Get cache ID paths
	 *
	 * @param array $cacheIds
	 * @return array
	 */
	private function queryCachePaths($cacheIds = array())
	{
		$paths = craft()->db->createCommand()
			->select('path')
			->from('templatecaches')
			->where(array('in', 'id', $cacheIds))
			->queryColumn();

		return str_replace('site:', '', $paths);
	}

	/**
	 * Get matching cache groups
	 *
	 * @param array $paths
	 * @param array $groups
	 * @param boolean $recursive
	 * @return array
	 */
	private function getCachePaths($paths, $groups, $recursive)
	{
		$cachePaths = array();

		// Find all paths in the cache directory tree
		foreach ($groups as $group) {
			foreach ($paths as $path) {
				$rootPath = $this->normalizePath(implode('/', array(
					craft()->config->get('rootPath', 'presto'),
					$this->settings->cachePath,
					$group
				)));

				$targetPath = $rootPath . '/' . $path;
				$cachePaths[] = $path;

				if (file_exists($targetPath)) {
					if ($recursive) {
						$iterator = new \RecursiveIteratorIterator(
							new \RecursiveDirectoryIterator($targetPath)
						);

						foreach ($iterator as $item) {
							if ($item->isDir()) {
								$cachePaths[] = $this->normalizePath(
									str_replace($rootPath . '/', '', $item->getPath())
								);
							}
						}
					}
				}
			}
		}

		return array_unique(
			array_reverse($cachePaths)
		);
	}

	/**
	 * Get the cache entries for a set of paths
	 *
	 * @param array $paths
	 * @param bool $expired
	 * @return mixed
	 */
	private function getCacheEntries($paths, $expired = false)
	{
		$paths = preg_filter('/^/', 'site:', $paths);

		$query = craft()->db->createCommand()
			->select('id,  REPLACE(path, "site:", "") AS path')
			->from('templatecaches')
			->where(array('in', 'path', $paths));

		if ($expired) {
			$query->andWhere(
				'expiryDate <= :now',
				array('now' => DateTimeHelper::currentTimeForDb())
			);
		}

		return $query->queryAll();
	}

	/**
	 * Get criteria data for a given element type
	 *
	 * @param string $type
	 * @return array
	 */
	private function getCriteria($type)
	{
		$query = craft()->db->createCommand()
			->from('templatecachecriteria');

		if (is_array($type)) {
			$query->where(array('in', 'type', $type));
		} else {
			$query->where('type = :type', array(
				':type' => $type
			));
		}

		return $query->queryAll();
	}

	/**
	 * Get direct cache IDs for given elements
	 *
	 * @param array $elementIds
	 * @return array
	 */
	private function getCaches($elementIds)
	{
		$query = craft()->db->createCommand()
			->select('cacheId')
			->from('templatecacheelements');

		if (is_array($elementIds)) {
			$query->where(array('in', 'elementId', $elementIds));
		} else {
			$query->where('elementId = :elementId', array(
				':elementId' => $elementIds
			));
		}

		return $query->queryColumn();
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