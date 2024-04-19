<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\CookieGroup;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\NotFoundHttpException;

class SettingsController extends Controller
{
	public function actionIndex()
	{
		$variables = [
			'content' => file_get_contents(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'README.md'),
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
	 * @param SiteSettings|null $model
	 *
	 * @return \yii\web\Response
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionEditSiteSettings(SiteSettings $model = null)
	{
		$this->requirePermission('cookie-consent:site-settings');

		Craft::$app->getRequest();
		$variables = [
			'model' => $model
		];
		$this->_prepVariables($variables);
		$variables['currentPage'] = 'site';
		$variables['title'] = Craft::t('cookie-consent', 'Site Settings');
        $variables['invalidate_link'] = "/" . Craft::$app->config->general->cpTrigger . "/cookie-consent/site/invalidate?site=".$variables['selectedSite']->handle;
		$this->_checkSiteEditPermission($variables['selectedSite']->id);
		$this->_prepSiteSettingsPermissionVariables($variables);

		return $this->renderTemplate('cookie-consent/settings/site', $variables);
	}

	/**
	 * Render the view for consent entries
     *
     * @param integer|null      $page
	 *
	 * @return \yii\web\Response
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionConsent($page = null)
	{
	    $pageSize = 20;
	    if($page == null) $page = 1;

		$this->requirePermission('cookie-consent:site-settings:view-consents');

		Craft::$app->getRequest();
		$variables = [
		];
		$this->_prepVariables($variables);
		$variables['currentPage'] = 'consent';
		$variables['consents'] = Consent::find()->where(['site_id' => $variables['selectedSite']->id])->orderBy('dateUpdated DESC')->limit($pageSize)->offset(($page-1)*$pageSize)->all();
		$total = Consent::find()->where(['site_id' => $variables['selectedSite']->id])->count();
		$count = count($variables['consents']);

		$cpTrigger = Craft::$app->config->general->cpTrigger;

		$from = (($page-1)*$pageSize)+1;
		$to = (($page-1)*$pageSize)+$count;
		$variables['pagination'] = [
		    'pageSize' => $pageSize,
            'currentPage' => $page,
            'from' => $from,
            'to' => $to,
            'previous' => $from > 1 ? "/{$cpTrigger}/cookie-consent/site/consent/".($page-1) : null,
            'next' => $to < $total ? "/{$cpTrigger}/cookie-consent/site/consent/".($page+1) : null,
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
     * @return \yii\web\Response
     */
    public function actionRetention()
    {
        Craft::$app->getRequest();
        $variables = [
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

    /**
     * @return \yii\web\Response
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     */
	public function actionInvalidateConsents()
    {
        $record = SiteSettings::findOne(Craft::$app->getSites()->getSiteByHandle(Craft::$app->request->get('site'))->id);
        $record->dateInvalidated = Db::prepareDateForDb(new \DateTime());
        if(!$record->save()) Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Couldn’t invalidate the consents.'));
        else Craft::$app->getSession()->setNotice(Craft::t('cookie-consent', 'Consents invalidated.'));

        return $this->redirect(UrlHelper::cpUrl('cookie-consent/site'));
    }

	/**
	 * Render the view for editing a CookieGroup
	 *
	 * @param string|null      $groupId
	 * @param CookieGroup|null $group
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException
	 * @throws \yii\web\ForbiddenHttpException
	 */
	public function actionEditCookieGroup(string $groupId = null, CookieGroup $group = null)
	{
		Craft::$app->getRequest();
		$variables = [
			'groupId' => $groupId,
			'group' => $group
		];
        $this->_prepVariables($variables);
		$this->_prepEditGroupVariables($variables);
        if($variables['selectedSite']->id !== $variables['group']->site_id) return $this->redirect(UrlHelper::cpUrl('cookie-consent?site=' . $variables['selectedSite']->handle));
		$this->_checkSiteEditPermission($variables['selectedSite']->id);
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
        $siteHandle = Craft::$app->request->get('site');
        if($siteHandle) $variables['selectedSite'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        else $variables['selectedSite'] = Craft::$app->getSites()->primarySite;
        $variables['fullPageForm'] = true;

        if (empty($variables['model'])) {
            $variables['model'] = SiteSettings::findOne($variables['selectedSite']->id);
            if (!$variables['model']) {
                $variables['model'] = new SiteSettings();
                $variables['model']->site_id = $variables['selectedSite']->id;
                $this->insertDefaultRecord($variables['model']);
            }
        }

        // Old
        /*
        $siteHandle = Craft::$app->request->get('site');
        /* @var $site Site */
        /*
        if($siteHandle) $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);
        else $site = Craft::$app->getSites()->primarySite;

        $variables['selectedSite'] = $site;
        $variables['cpTrigger'] = Craft::$app->config->general->cpTrigger;

        if (Craft::$app->getIsMultiSite()) {
            $sites = Craft::$app->getSites();
            $variables['sitesMenuLabel'] = $site->name;
            $variables['showSiteMenu'] = true;
            foreach ($sites->getEditableSiteIds() as $editableSiteId) {
                $variables['enabledSiteIds'][] = $editableSiteId;
                $variables['siteIds'][] = $editableSiteId;
            }
        } else {
            $variables['sitesMenuLabel'] = 'Cookie Consent';
        }

		if (empty($variables['model'])) {
			$variables['model'] = SiteSettings::findOne($variables['selectedSite']->id);
			if (!$variables['model']) {
				$variables['model'] = new SiteSettings();
				$variables['model']->site_id = $variables['selectedSite']->id;
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
				'label' => $variables['selectedSite']->name,
				'url' => UrlHelper::cpUrl('cookie-consent/site/'.$variables['selectedSite']->handle),
			]
		];
        */
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
				$variables['group']->site_id = $variables['selectedSite']->id;
			}
		}
		$variables['group']->unstringifyCookies();
		$variables['model'] = SiteSettings::findOne($variables['selectedSite']->id);

		$variables['currentPage'] = 'group';
		$variables['title'] = $variables['group']->isNewRecord ? Craft::t('cookie-consent', 'New cookie Group') : $variables['group']->name;
		$variables['fullPageForm'] = true;
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
