<?php
namespace Craft;

class PrestoTask extends BaseTask
{
	private $paths;

	public function getDescription() {
		return Craft::t('Refreshing Presto cache');
	}

	/**
	 * Return the total number of steps to queue
	 *
	 * @return int
	 */
	public function getTotalSteps() {
		// Cancel stale template cache task
		if ($task = craft()->tasks->getNextPendingTask('DeleteStaleTemplateCaches')) {
			craft()->tasks->deleteTaskById($this->id);
		}

		$this->paths = $this->getSettings()->paths;
		return count($this->paths);
	}

	/**
	 * Reset path cache values
	 *
	 * @param int $step
	 * @return bool
	 */
	public function runStep($step) {
		$path = $this->paths[$step];

		$warmers = craft()->config->get('warmers', 'presto');
		$warmers[] = array(
			'config' => false
		);

		foreach ($warmers as $warmer) {
			$fingerprint = isset($warmer['fingerprint']) ? $warmer['fingerprint'] : false;
			$headers = isset($warmer['headers']) ? $warmer['headers'] : array();
			$config = isset($warmer['config']) ? $warmer['config'] : array();

			$key = craft()->presto->generateKey($path, $fingerprint);

			craft()->presto->requestPage($path, $headers);

			if ($html = craft()->templateCache->getTemplateCache($key, true)) {
				craft()->presto->writeCache($path, $html, $config);
			}
		}

		return true;
	}

	protected function defineSettings() {
		return array(
			'paths' => AttributeType::Mixed
		);
	}
}