<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use elleracompany\cookieconsent\models\SiteSettings as SiteSettingsModel;
use elleracompany\cookieconsent\records\SiteSettings as SiteSettingsRecord;
use yii\web\NotFoundHttpException;

class SettingsController extends Controller
{
	/**
	 * @param string|null $siteHandle
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException
	 */
	function actionIndex(string $siteHandle = null)
	{
		$params = [];
		$siteId = $this->getSiteIdFromHandle($siteHandle);
		$params['currentSiteId'] = empty($siteId) ? Craft::$app->getSites()->currentSite->id : $siteId;
		$params['currentSiteHandle'] = empty($siteHandle) ? Craft::$app->getSites()->currentSite->handle : $siteHandle;
		$params['model'] = new SiteSettingsModel();
		$params['fullPageForm'] = true;

		return $this->renderTemplate('cookie-consent/settings/index', $params);
	}

	function actionSaveSiteSettings(string $siteHandle = null)
	{
		// TODO: Failed route
		// TODO: Fetch from record / save to record
		echo "<pre>";
		die(var_dump(['handle' => $siteHandle, 'POST' => $_POST]));
	}

	/**
	 * Return a siteId from a siteHandle
	 *
	 * @param string $siteHandle
	 *
	 * @return int|null
	 * @throws NotFoundHttpException
	 */
	protected function getSiteIdFromHandle($siteHandle)
	{
		if ($siteHandle !== null) {
			$site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
			if (!$site) {
				throw new NotFoundHttpException('Invalid site handle: '.$siteHandle);
			}
			$siteId = $site->id;
		} else {
			$siteId = Craft::$app->getSites()->currentSite->id;
		}

		return $siteId;
	}
}