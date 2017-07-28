<?php
namespace Craft;

class PrestoController extends BaseController
{
	/**
	 * Purge cache
	 *
	 * @return null
	 */
	public function actionPurgeCache()
	{
		$this->requireAdmin();
		$cron = craft()->config->get('purgeMethod', 'presto') === 'cron';

		if ($cron) {
			craft()->presto->storePurgeAllEvent();
		} else {
			// Clear static cache
			craft()->presto->purgeEntireCache();

			// Clear db template cache
			craft()->templateCache->deleteAllCaches();
		}

		craft()->userSession->setNotice(Craft::t($cron ? 'Cache purge scheduled.' : 'Cache purged.'));

		return $this->redirect(UrlHelper::getCpUrl('settings/plugins/presto'));
	}
}