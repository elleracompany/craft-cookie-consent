<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\web\Cookie;
use elleracompany\cookieconsent\CookieConsent;

class ConsentController extends Controller
{
	protected array|int|bool $allowAnonymous = true;
	/**
	 * Ajax Cookie Consent Store endpoint
	 *
	 * @return bool
	 */
	public function actionUpdate()
	{
		$save_ip = false;
		$post = Craft::$app->request->post();

		/** @var $site  SiteSettings */
		$site = SiteSettings::find()->where(['site_id' => $post['site_id']])->with('cookieGroups')->one();
		$consent = [];

		foreach ($site->cookieGroups as $group)
		{
            if(isset($post['acceptAll']) && $post['acceptAll'] == 'true') $consent[$group->slug] = true;
			elseif(isset($post['group-'.$group->slug])) $consent[$group->slug] = $post['group-'.$group->slug] == 'on' ? true : false;
			else $consent[$group->slug] = $group->required ? true : false;
			if($consent[$group->slug] && (int)$group->store_ip == 1) $save_ip = true;
		}

		$consentRecord = new Consent();
		$consentRecord->site_id = $post['site_id'];
		$consentRecord->ip = $save_ip ? Craft::$app->request->getUserIP() : null;
        $consentRecord->cookieName = $site->cookieName;
		$consentRecord->data = json_encode($consent);
		$consentRecord->save();

		Craft::$app->response->format = Response::FORMAT_JSON;

		$cookie = new Cookie([
		    'name' => $site->cookieName,
            'value' => json_encode(array_merge(['consent_uid' => $consentRecord->uid],$consent)),
            'expire' => strtotime('+1 year', time()),
			'secure' => true,
        ]);

		Craft::$app->response->cookies->add($cookie);

        return true;
	}

    public function actionShow()
    {
        Craft::$app->response->format = Response::FORMAT_JSON;
        return CookieConsent::getInstance()->cookieConsent->getConsents();
    }
}