<?php
namespace Craft;

class PrestoController extends BaseController
{
	/**
	 * Display settings index
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		return $this->renderTemplate('presto/index', [
			'settings' => craft()->plugins->getPlugin('presto')->getSettings()
		]);
	}

	/**
	 * Save settings
	 *
	 * @return bool
	 */
	public function actionSaveSettings()
	{
		$this->requirePostRequest();

		craft()->plugins->savePluginSettings(
			craft()->plugins->getPlugin('Presto'), [
				'cachePath' => craft()->request->getPost('cachePath'),
				'rootPath' => craft()->config->get('rootPath', 'presto')
			]
		);

		craft()->userSession->setNotice(Craft::t('Plugin settings saved.'));

		return false;
	}

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