<?php
namespace Craft;

class PrestoController extends BaseController
{
	/**
	 * Handle requests to schedule a cache purge
	 */
	public function actionPurgeCache()
	{
		$this->requireAdmin();

		craft()->presto->storePurgeAllEvent();
		craft()->userSession->setNotice(Craft::t('Cache purge scheduled.'));

		return $this->redirect(UrlHelper::getCpUrl('presto'));
	}
}