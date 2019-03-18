<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\CookieGroup;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\NotFoundHttpException;
use craft\helpers\UrlHelper;

class SettingsController extends Controller
{
	/**
	 * @param string|null $siteHandle
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException
	 * @throws \craft\errors\MissingComponentException
	 */
	public function actionIndex(string $siteHandle = null)
	{
		$params = [];
		if(Craft::$app->request->isPost) {
			$record = SiteSettings::findOne(Craft::$app->request->post('site_id'));
			if(!$record) {
				$record = new SiteSettings();
			}
			$record->load(Craft::$app->request->post(), '');
			$siteId = $record->site_id;
			if($record->save()) Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Settings saved.'));
			else Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t save the settings.'));
		}
		else {
			$siteId = $this->getSiteIdFromHandle($siteHandle);
			$record = SiteSettings::findOne($siteId);
			if(!$record) {
				$record = new SiteSettings();
				$record->template = 'cookie-consent/banner';
			}
		}

		$params['currentSiteId'] = empty($siteId) ? Craft::$app->getSites()->currentSite->id : $siteId;
		$params['currentSiteHandle'] = empty($siteHandle) ? Craft::$app->getSites()->currentSite->handle : $siteHandle;

		$params['currentPage'] = 'site';
		$params['model'] = $record;
		$params['title'] = Craft::t('cookie-consent', 'Site Settings');
		$params['fullPageForm'] = true;
		$params['crumbs'] = [
			[
				'label' => CookieConsent::PLUGIN_NAME,
				'url' => UrlHelper::cpUrl('cookie-consent'),
			]
		];

		return $this->renderTemplate('cookie-consent/settings/index', $params);
	}

	/**
	 * @param string|null $siteHandle
	 * @param string|null $sectionId
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException
	 * @throws \craft\errors\MissingComponentException
	 */
	public function actionGroup(string $siteHandle = null, string $sectionId = null)
	{
		$params = [];


		if(Craft::$app->request->isPost)
		{
			$record = CookieGroup::findOne([
				'id' => Craft::$app->request->post('id')
			]);
			if(!$record) {
				$record = new CookieGroup();
			}
			$record->load(Craft::$app->request->post(), '');
			$siteId = $record->site_id;
			$site = Craft::$app->sites->getSiteById($siteId);
			$siteHandle = $site->handle;
			if($record->required) $record->default = true;
			if($record->save()) {
				Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Cookie group saved.'));
			}
			else Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t save the gookie group.'));
		}
		else {
			$record = $sectionId == null ? new CookieGroup() : CookieGroup::findOne([
				'id' => $sectionId
			]);
			if(!$record) {
				$record = new CookieGroup();
			}
			$siteId = $this->getSiteIdFromHandle($siteHandle);
			$site = Craft::$app->sites->getSiteById($siteId);
			$record->site_id = $siteId;
		}
		$params['currentSiteId'] = empty($siteId) ? Craft::$app->getSites()->currentSite->id : $siteId;
		$params['currentSiteHandle'] = empty($siteHandle) ? Craft::$app->getSites()->currentSite->handle : $siteHandle;

		$siteModel = SiteSettings::findOne($siteId);
		if(!$siteModel) return $this->redirect('/admin/cookie-consent/site/'.$siteHandle);

		$params['model'] = $siteModel;
		$params['group'] = $record;
		$params['currentPage'] = 'group';
		$params['title'] = $record->isNewRecord ? Craft::t('cookie-consent', 'New cookie Group') : $record->name;
		$params['fullPageForm'] = true;
		$params['crumbs'] = [
			[
				'label' => CookieConsent::PLUGIN_NAME,
				'url' => UrlHelper::cpUrl('cookie-consent'),
			],
			[
				'label' => $site->name,
				'url' => UrlHelper::cpUrl('cookie-consent/site/'.$site->handle),
			]
		];

		return $this->renderTemplate('cookie-consent/settings/group', $params);
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