<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\CookieGroup;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public function actionIndex(): Response
    {
        $readme = dirname(CookieConsent::getInstance()->getBasePath()).DIRECTORY_SEPARATOR.'README.md';
        $variables = [
            'content' => is_file($readme) ? file_get_contents($readme) : '',
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
     * @return Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionEditSiteSettings(?SiteSettings $model = null): Response
    {
        $this->requirePermission('cookie-consent:site-settings');

        $variables = [
            'model' => $model
        ];
        $this->_prepVariables($variables);
        $variables['currentPage'] = 'site';
        $variables['title'] = Craft::t('cookie-consent', 'Site Settings');
        $this->_checkSiteEditPermission($variables['selectedSite']->id);
        $this->_prepSiteSettingsPermissionVariables($variables);

        return $this->renderTemplate('cookie-consent/settings/site', $variables);
    }

    /**
     * Render the view for consent entries
     *
     * @param int|null $page
     *
     * @return Response
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionConsent(?int $page = null): Response
    {
        $pageSize = 10;
        if (!$page) {
            $page = 1;
        }

        $this->requirePermission('cookie-consent:site-settings:view-consents');

        $variables = [];
        $this->_prepVariables($variables);
        $variables['currentPage'] = 'consent';
        $variables['consents'] = Consent::find()->where(['site_id' => $variables['selectedSite']->id])->orderBy('dateUpdated DESC')->limit($pageSize)->offset(($page-1)*$pageSize)->all();
        $total = Consent::find()->where(['site_id' => $variables['selectedSite']->id])->count();
        $count = count($variables['consents']);

        $siteParam = ['site' => $variables['selectedSite']->handle];

        $from = (($page-1)*$pageSize)+1;
        $to = (($page-1)*$pageSize)+$count;
        $variables['pagination'] = [
            'pageSize' => $pageSize,
            'currentPage' => $page,
            'from' => $from,
            'to' => $to,
            'previous' => $from > 1 ? UrlHelper::cpUrl('cookie-consent/site/consent/'.($page-1), $siteParam) : null,
            'next' => $to < $total ? UrlHelper::cpUrl('cookie-consent/site/consent/'.($page+1), $siteParam) : null,
            'current' => $count,
            'total' => $total
        ];

        $variables['title'] = Craft::t('cookie-consent', 'Consents');

        $this->_prepSiteSettingsPermissionVariables($variables);

        return $this->renderTemplate('cookie-consent/settings/consent', $variables);
    }

    /**
     * Save site settings
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionSaveSiteSettings(): ?Response
    {
        $this->requirePostRequest();

        $this->_checkSiteEditPermission((int) Craft::$app->request->post('site_id'));

        $record = SiteSettings::findOne(Craft::$app->request->post('site_id'));
        if (!$record) {
            throw new NotFoundHttpException('Settings for site not found');
        }
        $record->load(Craft::$app->request->post(), '');
        foreach (['activated', 'jsAssets', 'cssAssets', 'templateAsset', 'showCheckboxes', 'showAfterConsent'] as $lightswitch) {
            $record->$lightswitch = (bool) $record->$lightswitch;
        }

        if (!$record->save()) {
            return $this->asModelFailure($record, Craft::t('cookie-consent', 'Couldn’t save the settings.'), 'model');
        }

        $site = Craft::$app->getSites()->getSiteById((int) $record->site_id);
        return $this->asModelSuccess(
            $record,
            Craft::t('cookie-consent', 'Settings saved.'),
            'model',
            [],
            UrlHelper::cpUrl('cookie-consent/site', $site ? ['site' => $site->handle] : [])
        );
    }

    /**
     * Invalidate all consents for a site by bumping its dateInvalidated
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionInvalidateConsents(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('cookie-consent:site-settings');

        $siteId = (int) Craft::$app->request->getRequiredBodyParam('site_id');
        $this->_checkSiteEditPermission($siteId);

        $record = SiteSettings::findOne($siteId);
        if (!$record) {
            throw new NotFoundHttpException('Settings for site not found');
        }

        $record->dateInvalidated = Db::prepareDateForDb(new \DateTime());
        if (!$record->save()) {
            return $this->asFailure(Craft::t('cookie-consent', 'Couldn’t invalidate the consents.'));
        }

        $site = Craft::$app->getSites()->getSiteById($siteId);
        return $this->asSuccess(
            Craft::t('cookie-consent', 'Consents invalidated.'),
            [],
            UrlHelper::cpUrl('cookie-consent/site', $site ? ['site' => $site->handle] : [])
        );
    }

    /**
     * Render the view for editing a CookieGroup
     *
     * @param string|null $groupId
     * @param CookieGroup|null $group
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionEditCookieGroup(?string $groupId = null, ?CookieGroup $group = null): Response
    {
        $variables = [
            'groupId' => $groupId,
            'group' => $group
        ];
        $this->_prepVariables($variables);
        $this->_prepEditGroupVariables($variables);
        // site_id may be a string when the group was repopulated from POST after
        // a failed validation - a strict int/string mismatch here would redirect
        // away and swallow the validation errors (#96)
        if ($variables['selectedSite']->id !== (int) $variables['group']->site_id) {
            return $this->redirect(UrlHelper::cpUrl('cookie-consent?site=' . $variables['selectedSite']->handle));
        }
        $this->_checkSiteEditPermission($variables['selectedSite']->id);
        $this->_prepGroupPermissionVariables($variables);

        return $this->renderTemplate('cookie-consent/settings/group', $variables);
    }

    /**
     * Save cookie group
     *
     * @return Response|null
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\web\BadRequestHttpException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionSaveCookieGroup(): ?Response
    {
        $this->requirePostRequest();

        $id = Craft::$app->request->post('id');

        $record = CookieGroup::findOne([
            'id' => is_numeric($id) ? $id : null
        ]);
        if (!$record) {
            $this->requirePermission('cookie-consent:cookie-groups:create-new');
            $record = new CookieGroup();
        } else {
            $this->requirePermission('cookie-consent:cookie-groups:edit');
        }
        $record->load(Craft::$app->request->post(), '');

        $this->_checkSiteEditPermission((int) $record->site_id);

        if ($record->required) {
            $record->default = true;
        }
        if (!$record->save()) {
            return $this->asModelFailure($record, Craft::t('cookie-consent', 'Couldn’t save the cookie group.'), 'group');
        }
        return $this->asModelSuccess(
            $record,
            Craft::t('cookie-consent', 'Cookie group saved.'),
            'group',
            [],
            $record->getEditUrl()
        );
    }

    /**
     * Deletes cookie group
     *
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\ForbiddenHttpException
     */
    public function actionDeleteCookieGroup(): ?Response
    {
        $this->_checkSiteEditPermission((int) Craft::$app->request->getParam('site_id'));
        $this->requirePermission('cookie-consent:cookie-groups:delete');
        $group = CookieGroup::findOne([
            'id' => Craft::$app->request->getParam('id'),
            'site_id' => Craft::$app->request->getParam('site_id')
        ]);
        if (!$group) {
            throw new NotFoundHttpException('Cookie group not found');
        }
        if (!$group->delete()) {
            return $this->asFailure(Craft::t('cookie-consent', 'Couldn’t delete the cookie group.'));
        }
        return $this->asSuccess(
            Craft::t('cookie-consent', 'Cookie group deleted.'),
            [],
            UrlHelper::cpUrl('cookie-consent/site')
        );
    }

    /**
     * Populate SiteSettings record with
     * default data
     *
     * @param SiteSettings $record
     */
    protected function insertDefaultRecord(SiteSettings $record): void
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
    private function _prepVariables(array &$variables): void
    {
        $siteHandle = Craft::$app->request->get('site');
        $variables['selectedSite'] = ($siteHandle ? Craft::$app->getSites()->getSiteByHandle($siteHandle) : null)
            ?? Craft::$app->getSites()->primarySite;
        $variables['fullPageForm'] = true;

        if (empty($variables['model'])) {
            $variables['model'] = SiteSettings::findOne($variables['selectedSite']->id);
            if (!$variables['model']) {
                $variables['model'] = new SiteSettings();
                $variables['model']->site_id = $variables['selectedSite']->id;
                $this->insertDefaultRecord($variables['model']);
            }
        }
    }

    /**
     * Prepare twig variables for Group Edit
     *
     * @param array $variables
     *
     * @throws NotFoundHttpException
     */
    private function _prepEditGroupVariables(array &$variables): void
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
    private function _checkSiteEditPermission(int $siteId): void
    {
        if (Craft::$app->getIsMultiSite()) {
            $editableSiteIds = Craft::$app->getSites()->getEditableSiteIds();
            if (!\in_array($siteId, $editableSiteIds, true)) {
                $this->requirePermission('editSite:'.$siteId);
            }
        }
    }

    private function _prepGroupPermissionVariables(array &$variables): void
    {
        $variables['canCreate'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:create-new');
        $variables['canEdit'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:edit');
        $variables['canDelete'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:delete');
        $variables['canGroups'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups');
    }

    private function _prepSiteSettingsPermissionVariables(array &$variables): void
    {
        $variables['canActivate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:activate');
        $variables['canChangeTemplate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:template');
        $variables['canUpdate'] = Craft::$app->user->checkPermission('cookie-consent:site-settings:content');
        $variables['canCreate'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups:create-new');
        $variables['canGroups'] = Craft::$app->user->checkPermission('cookie-consent:cookie-groups');
    }
}
