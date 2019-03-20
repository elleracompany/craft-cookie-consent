<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\records\Site;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\CookieGroup;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\NotFoundHttpException;
use craft\helpers\UrlHelper;

class SettingsController extends Controller
{
	/**
	 * Render the view for editing SiteSettings
	 *
	 * @param string|null       $siteHandle
	 * @param SiteSettings|null $model
	 *
	 * @return \yii\web\Response
	 */
	public function actionEditSiteSettings(string $siteHandle = null, SiteSettings $model = null)
	{

		Craft::$app->getRequest();
		$variables = [
			'currentSiteHandle' => $siteHandle,
			'model' => $model
		];
		$this->_prepEditSiteSettingsVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/index', $variables);
	}

	/**
	 * Save site settings
	 *
	 * @throws NotFoundHttpException
	 * @throws \craft\errors\MissingComponentException
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function actionSaveSiteSettings()
	{
		$this->requirePostRequest();

		$record = SiteSettings::findOne(Craft::$app->request->post('site_id'));
		if(!$record) {
			throw new NotFoundHttpException('Settings for site not found');
		}
		$record->load(Craft::$app->request->post(), '');
		if($record->save()) {
			Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Settings saved.'));
		}
		else {
			Craft::$app->getUrlManager()->setRouteParams([
				'model' => $record
			]);
			Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t save the settings.'));
		}
		return null;
	}

	/**
	 * Render the view for editing a CookieGroup
	 *
	 * @param string|null      $siteHandle
	 * @param string|null      $groupId
	 * @param CookieGroup|null $group
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException
	 */
	public function actionEditCookieGroup(string $siteHandle = null, string $groupId = null, CookieGroup $group = null)
	{
		Craft::$app->getRequest();
		$variables = [
			'currentSiteHandle' => $siteHandle,
			'groupId' => $groupId,
			'group' => $group
		];
		$this->_prepEditGroupVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/group', $variables);
	}

	/**
	 * Save cookie group
	 *
	 * @throws \craft\errors\MissingComponentException
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function actionSaveCookieGroup()
	{
		$this->requirePostRequest();

		$record = CookieGroup::findOne([
			'id' => Craft::$app->request->post('id')
		]);
		if(!$record) {
			$record = new CookieGroup();
		}
		$record->load(Craft::$app->request->post(), '');
		if($record->required) $record->default = true;
		if($record->save()) Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Cookie group saved.'));
		else {
			Craft::$app->getUrlManager()->setRouteParams([
				'group' => $record
			]);
			Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t save the cookie group.'));
		}
		return null;
	}

	/**
	 * Return a siteId from a siteHandle
	 *
	 * @param string $siteHandle
	 *
	 * @return int
	 * @throws NotFoundHttpException
	 */
	protected function getSiteIdFromHandle($siteHandle) : int
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

	/**
	 * Populate SiteSettings record with
	 * default data
	 *
	 * @param SiteSettings $record
	 */
	protected function insertDefaultRecord(SiteSettings &$record)
	{
		$record->template = CookieConsent::DEFAULT_TEMPLATE;
		$record->headline = CookieConsent::DEFAULT_HEADLINE;
		$record->activated = false;
		$record->save();
		foreach (CookieConsent::DEFAULT_GROUPS as $group)
		{
			$m = new CookieGroup();
			$m->required = $group['required'];
			$m->site_id = $record->site_id;
			$m->store_ip = $group['store_ip'];
			$m->default = $group['default'];
			$m->name = $group['name'];
			$m->description = $group['description'];
			$m->cookies = $group['cookies'];
			$m->save();
		}
	}

	/**
	 * Prepare twig variables for Site Settings
	 *
	 * @param array $variables
	 */
	private function _prepEditSiteSettingsVariables(array &$variables)
	{
		if(empty($variables['currentSiteHandle']))
		{
			$variables['site'] = Craft::$app->getSites()->currentSite;
			$variables['currentSiteId'] = $variables['site']->id;
			$variables['currentSiteHandle'] = $variables['site']->handle;
		}
		else {
			$variables['site'] = Craft::$app->sites->getSiteByHandle($variables['currentSiteHandle']);
			$variables['currentSiteId'] = $variables['site']->id;
		}
		if (empty($variables['model'])) {
			$variables['model'] = SiteSettings::findOne($variables['currentSiteId']);
			if (!$variables['model']) {
				$variables['model'] = new SiteSettings();
				$variables['model']->site_id = $variables['currentSiteId'];
				$this->insertDefaultRecord($variables['model']);
			}
		}
		$variables['model'] = SiteSettings::findOne($variables['currentSiteId']);

		$variables['currentPage'] = 'site';
		$variables['title'] = Craft::t('cookie-consent', 'Site Settings');
		$variables['fullPageForm'] = true;
		$variables['crumbs'] = [
			[
				'label' => CookieConsent::PLUGIN_NAME,
				'url' => UrlHelper::cpUrl('cookie-consent'),
			],
			[
				'label' => $variables['site']->name,
				'url' => UrlHelper::cpUrl('cookie-consent/site/'.$variables['site']->handle),
			]
		];
	}

	/**
	 * Prepare twig variables for Group Edit
	 *
	 * @param array $variables
	 *
	 * @throws NotFoundHttpException
	 */
	private function _prepEditGroupVariables(array &$variables)
	{
		if(empty($variables['currentSiteHandle']))
		{
			$variables['site'] = Craft::$app->getSites()->currentSite;
			$variables['currentSiteId'] = $variables['site']->id;
			$variables['currentSiteHandle'] = $variables['site']->handle;
		}
		else {
			$variables['site'] = Craft::$app->sites->getSiteByHandle($variables['currentSiteHandle']);
			$variables['currentSiteId'] = $variables['site']->id;
		}
		if (empty($variables['group'])) {
			if (!empty($variables['groupId'])) {
				$variables['group'] = CookieGroup::findOne([
					'id' => $variables['groupId']
				]);
				if (!$variables['group']) {
					throw new NotFoundHttpException('Group not found');
				}
			} else {
				$variables['group'] = new CookieGroup();
				$variables['group']->site_id = $variables['currentSiteId'];
			}
		}
		$variables['group']->unstringifyCookies();
		$variables['model'] = SiteSettings::findOne($variables['currentSiteId']);

		$variables['currentPage'] = 'group';
		$variables['title'] = $variables['group']->isNewRecord ? Craft::t('cookie-consent', 'New cookie Group') : $variables['group']->name;
		$variables['fullPageForm'] = true;
		$variables['crumbs'] = [
			[
				'label' => CookieConsent::PLUGIN_NAME,
				'url' => UrlHelper::cpUrl('cookie-consent'),
			],
			[
				'label' => $variables['site']->name,
				'url' => UrlHelper::cpUrl('cookie-consent/site/'.$variables['currentSiteHandle']),
			]
		];
	}
}