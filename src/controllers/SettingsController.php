<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\helpers\Db;
use craft\models\Site;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\CookieGroup;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\NotFoundHttpException;
use craft\helpers\UrlHelper;

class SettingsController extends Controller
{
	public function actionIndex(string $siteHandle = null)
	{
		$variables = [
			'content' => file_get_contents(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'README.md'),
            'currentSiteHandle' => $siteHandle,
			'site' => Craft::$app->getSites()->currentSite
		];
		$this->_prepVariables($variables);
		$variables['currentPage'] = 'readme';
		$variables['title'] = Craft::t('cookie-consent', 'Readme');
		$this->_prepSiteSettingsPermissionVariables($variables);
		return $this->renderTemplate('cookie-consent/settings/index', $variables);
	}

	/**
	 * Render the view for editing SiteSettings
	 *
	 * @param string|null       $siteHandle
	 * @param SiteSettings|null $model
	 *
	 * @return \yii\web\Response
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionEditSiteSettings(string $siteHandle = null, SiteSettings $model = null)
	{
		$this->requirePermission('cookie-consent:site-settings');

		Craft::$app->getRequest();
		$variables = [
			'currentSiteHandle' => $siteHandle,
			'model' => $model
		];
		$this->_prepVariables($variables);
		$variables['currentPage'] = 'site';
		$variables['title'] = Craft::t('cookie-consent', 'Site Settings');
		$this->_checkSiteEditPermission($variables['currentSiteId']);
		$this->_prepSiteSettingsPermissionVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/site', $variables);
	}

	/**
	 * Render the view for consent entries
	 *
	 * @param string|null       $siteHandle
     * @param integer|null      $page
	 *
	 * @return \yii\web\Response
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionConsent(string $siteHandle = null, $page = null)
	{
	    $pageSize = 20;
	    if($page == null) $page = 1;

		$this->requirePermission('cookie-consent:site-settings:view-consents');

		Craft::$app->getRequest();
		$variables = [
			'currentSiteHandle' => $siteHandle,
		];
		$this->_prepVariables($variables);
		$variables['currentPage'] = 'consent';
		$variables['consents'] = Consent::find()->where(['site_id' => $variables['currentSiteId']])->orderBy('dateUpdated DESC')->limit($pageSize)->offset(($page-1)*$pageSize)->all();
		$total = Consent::find()->where(['site_id' => $variables['currentSiteId']])->count();
		$count = count($variables['consents']);

		$cpTrigger = Craft::$app->config->general->cpTrigger;

		$from = (($page-1)*$pageSize)+1;
		$to = (($page-1)*$pageSize)+$count;
		$variables['pagination'] = [
		    'pageSize' => $pageSize,
            'currentPage' => $page,
            'from' => $from,
            'to' => $to,
            'previous' => $from > 1 ? "/{$cpTrigger}/cookie-consent/site/{$siteHandle}/consent/".($page-1) : null,
            'next' => $to < $total ? "/{$cpTrigger}/cookie-consent/site/{$siteHandle}/consent/".($page+1) : null,
            'current' => $count,
            'total' => $total
        ];

		$variables['title'] = Craft::t('cookie-consent', 'Consents');
		$this->_prepSiteSettingsPermissionVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/consent', $variables);
	}

    /**
     * Render the view for consent retention
     *
     * @param string|null       $siteHandle
     *
     * @return \yii\web\Response
     */
    public function actionRetention(string $siteHandle = null)
    {
        Craft::$app->getRequest();
        $variables = [
            'currentSiteHandle' => $siteHandle,
        ];
        $this->_prepVariables($variables);
        $variables['currentPage'] = 'retention';
        $variables['title'] = Craft::t('cookie-consent', 'Consent Retention');
        $this->_prepSiteSettingsPermissionVariables($variables);

        return $this->renderTemplate('cookie-consent/settings/retention', $variables);
    }

	/**
	 * Save site settings
	 *
	 * @return null
	 * @throws NotFoundHttpException
	 * @throws \craft\errors\MissingComponentException
	 * @throws \yii\web\BadRequestHttpException
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionSaveSiteSettings()
	{
		$this->requirePostRequest();

		$this->_checkSiteEditPermission(Craft::$app->request->post('site_id'));

		$record = SiteSettings::findOne(Craft::$app->request->post('site_id'));
		if(!$record) {
			throw new NotFoundHttpException('Settings for site not found');
		}
		$record->load(Craft::$app->request->post(), '');
		$record->activated = (int) $record->activated;
		$record->jsAssets = (int) $record->jsAssets;
		$record->cssAssets = (int) $record->cssAssets;
		$record->templateAsset = (int) $record->templateAsset;
		$record->showCheckboxes = (int) $record->showCheckboxes;
		$record->showAfterConsent = (int) $record->showAfterConsent;
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

	public function actionInvalidateConsents(string $siteHandle)
    {
        $site = Craft::$app->sites->getSiteByHandle($siteHandle);
        $record = SiteSettings::findOne($site->id);
        $record->dateInvalidated = Db::prepareDateForDb(new \DateTime());
        if(!$record->save()) Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t invalidate the consents.'));
        else Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Consents invalidated.'));

        return $this->redirect(UrlHelper::cpUrl('cookie-consent/site/'.$siteHandle));
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
	 * @throws \yii\web\ForbiddenHttpException
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
		$this->_checkSiteEditPermission($variables['currentSiteId']);
		$this->_prepGroupPermissionVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/group', $variables);
	}

	/**
	 * Save cookie group
	 *
	 * @return null|\yii\web\Response
	 * @throws \craft\errors\MissingComponentException
	 * @throws \yii\web\BadRequestHttpException
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionSaveCookieGroup()
	{
		$this->requirePostRequest();

		$id = Craft::$app->request->post('id');

		$record = CookieGroup::findOne([
			'id' => is_numeric($id) ? $id : null
		]);
		if(!$record) {
			$this->requirePermission('cookie-consent:cookie-groups:create');
			$record = new CookieGroup();
		}
		else $this->requirePermission('cookie-consent:cookie-groups:edit');
		$record->load(Craft::$app->request->post(), '');

		$this->_checkSiteEditPermission($record->site_id);

		if($record->required) $record->default = true;
		if($record->save()) Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Cookie group saved.'));
		else {
			Craft::$app->getUrlManager()->setRouteParams([
				'group' => $record
			]);
			Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t save the cookie group.'));
			return null;
		}
		return $this->redirect($record->getEditUrl());
	}

	/**
	 * Deletes cookie group
	 *
	 * @throws NotFoundHttpException
	 * @throws \Throwable
	 * @throws \yii\db\StaleObjectException
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionDeleteCookieGroup()
	{
		$this->_checkSiteEditPermission(Craft::$app->request->getParam('site_id'));
		$this->requirePermission('cookie-consent:cookie-groups:delete');
		$group = CookieGroup::findOne([
			'id' => Craft::$app->request->getParam('id'),
			'site_id' => Craft::$app->request->getParam('site_id')
		]);
		if(!$group) throw new NotFoundHttpException('Cookie group not found');
		if($group->delete()) Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Cookie group deleted.'));;
		return $this->redirect(UrlHelper::cpUrl('cookie-consent/site/'.
			Craft::$app->getSites()->getSiteById(Craft::$app->request->getParam('site_id'))->handle
		));
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
		$record->description = CookieConsent::DEFAULT_DESCRIPTION;
		$record->activated = false;
		$record->cssAssets = true;
		$record->jsAssets = true;
		$record->templateAsset = true;
		$record->showCheckboxes = true;
		$record->showAfterConsent = true;
		$record->save(false);
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
	private function _prepVariables(array &$variables)
	{
		if(empty($variables['currentSiteHandle']))
		{
            /* @var $site Site */
            $site = Craft::$app->getSites()->getEditableSites()[0];
            $variables['site'] = $site;
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

	/**
	 * Check if the user can edit the current site
	 *
	 * @param int $siteId
	 *
	 * @throws \yii\web\ForbiddenHttpException
	 */
	private function _checkSiteEditPermission(int $siteId)
	{
		if (Craft::$app->getIsMultiSite()) {

			$variables['editableSites'] = Craft::$app->getSites()->getEditableSiteIds();

			if (!\in_array($siteId, $variables['editableSites'], false)) {
					$this->requirePermission('editSite:'.$siteId);
			}
		}
	}

	private function _prepGroupPermissionVariables(array &$variables)
	{
		$variables['canCreate'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:create-new');
		$variables['canEdit'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:edit');
		$variables['canDelete'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:delete');
		$variables['canGroups'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups');
	}

	private function _prepSiteSettingsPermissionVariables(array &$variables)
	{
		$variables['canActivate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:activate');
		$variables['canChangeTemplate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:template');
		$variables['canUpdate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:content');
		$variables['canCreate'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:create-new');
		$variables['canGroups'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups');
	}
}