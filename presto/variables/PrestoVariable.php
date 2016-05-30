<?php
namespace Craft;

class PrestoVariable
{
	/**
	 * Generate cache for cacheable content when the plugin tag is present
	 *
	 * @param array $config (optional) {
	 *     @var string $group
	 *     @var bool $static
	 * }
	 * @return string
	 */
	public function cache($config = array())
	{
		$path = craft()->request->getPathInfo();
		$key = craft()->presto->generateKey($path);

		craft()->attachEventHandler('onEndRequest', function() use ($config, $path, $key) {
			if ((! isset($config['static']) || $config['static'] !== false) &&
				craft()->presto->isCacheable()
			) {
				if ($html = craft()->templateCache->getTemplateCache($key, true)) {
					craft()->presto->writeCache($path, $html, $config);
				}
			}
		});

		return $key;
	}

	/**
	 * Purge a set of files from the Presto cache
	 *
	 * @param array $config (optional) {
	 *     @var bool $expired
	 *     @var array $paths
	 *     @var bool $recursive
	 *     @var bool $warm
	 * }
	 */
	public function purge($config = array())
	{
		craft()->attachEventHandler('onEndRequest', function() use ($config) {
			craft()->presto->purgeCache($config);
		});
	}
}