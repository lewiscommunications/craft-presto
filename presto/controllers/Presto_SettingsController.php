<?php
namespace Craft;

class Presto_SettingsController extends BaseController
{
	/**
	 * Display settings index
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		return $this->renderTemplate('presto/_settings', [
			'settings' => craft()->plugins->getPlugin('presto')->getSettings()
		]);
	}
}