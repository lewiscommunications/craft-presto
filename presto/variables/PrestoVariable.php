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
		$host = craft()->request->getServerName();
		$path = craft()->request->getPathInfo();

		$keySegments = [
			'host' => $host,
			'path' => $path
		];

		if (isset($config['group']) && $config['group']) {
			$keySegments['group'] = $config['group'];
		}

		$key = craft()->presto->generateKey($keySegments);

		craft()->attachEventHandler('onEndRequest', function() use ($config, $host, $path, $key) {
			if ((! isset($config['static']) || $config['static'] !== false) &&
				craft()->presto->isCacheable()
			) {
				if ($html = craft()->templateCache->getTemplateCache($key, true)) {
					craft()->presto->writeCache($host, $path, $html, $config);
				}
			}
		});

		return $key;
	}
}