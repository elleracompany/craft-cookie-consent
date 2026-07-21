<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\BadRequestHttpException;
use yii\web\Cookie;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class ConsentController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    /**
     * Ajax Cookie Consent Store endpoint
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function actionUpdate(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $siteId = $request->getRequiredBodyParam('site_id');

        /** @var SiteSettings|null $site */
        $site = SiteSettings::find()->where(['site_id' => $siteId])->with('cookieGroups')->one();
        if (!$site) {
            throw new BadRequestHttpException('No cookie consent settings exist for site '.$siteId);
        }

        $saveIp = false;
        $acceptAll = $request->getBodyParam('acceptAll') === 'true';
        $consent = [];

        foreach ($site->cookieGroups as $group) {
            if ($acceptAll) {
                $consent[$group->slug] = true;
            } elseif ($request->getBodyParam('group-'.$group->slug) !== null) {
                $consent[$group->slug] = $request->getBodyParam('group-'.$group->slug) === 'on';
            } else {
                $consent[$group->slug] = (bool) $group->required;
            }
            if ($consent[$group->slug] && $group->store_ip) {
                $saveIp = true;
            }
        }

        $consentRecord = new Consent();
        $consentRecord->site_id = $site->site_id;
        $consentRecord->ip = $saveIp ? $request->getUserIP() : null;
        $consentRecord->cookieName = $site->cookieName;
        $consentRecord->data = json_encode($consent);

        if (!$consentRecord->save()) {
            throw new ServerErrorHttpException('Could not save the consent.');
        }

        $expiryDays = max(1, (int) $site->cookieExpiry ?: 365);
        Craft::$app->response->cookies->add(new Cookie([
            'name' => $site->cookieName,
            'value' => json_encode(array_merge(['consent_uid' => $consentRecord->uid], $consent)),
            'expire' => time() + $expiryDays * 86400,
            'secure' => true,
            'sameSite' => Cookie::SAME_SITE_LAX,
        ]));

        // Graceful fallback when the banner form is submitted natively
        // (JS disabled or not yet bound): return to the originating page
        // instead of rendering raw JSON (#116)
        if (!$request->getAcceptsJson()) {
            return $this->redirect($request->getReferrer() ?: '/');
        }

        return $this->asJson([
            'success' => true,
            'uid' => $consentRecord->uid,
            'consent' => $consent,
        ]);
    }

    public function actionShow(): Response
    {
        return $this->asJson(CookieConsent::getInstance()->cookieConsent->getConsents());
    }

    /**
     * Ajax CSRF token endpoint, fetched by the banner script right before
     * submitting consent. The token is not rendered into the banner markup
     * because generating it would mark the whole page as uncacheable.
     *
     * @return Response
     */
    public function actionCsrf(): Response
    {
        return $this->asJson([
            'name' => Craft::$app->config->general->csrfTokenName,
            'value' => Craft::$app->request->getCsrfToken(),
        ]);
    }
}
