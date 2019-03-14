<?php

namespace elleracompany\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;

class ConsentController extends Controller
{
	public function actionUpdate()
	{
		$save_ip = false;
		$post = Craft::$app->request->post();
		$site = SiteSettings::find()->where(['site_id' => $post['site_id']])->with('cookieGroups')->one();
		$consent = [];
		foreach ($site->cookieGroups as $group)
		{
			if(isset($post['group-'.$group->slug])) $consent[$group->slug] = $post['group-'.$group->slug] == 'on' ? true : false;
			else $consent[$group->slug] = $group->required ? true : false;
			if($consent[$group->slug] && (int)$group->store_ip == 1) $save_ip = true;
		}
		$consentRecord = new Consent();
		$consentRecord->site_id = $post['site_id'];
		$consentRecord->ip = $save_ip ? Craft::$app->request->getUserIP() : '***.***.***.***';
		$consentRecord->data = json_encode($consent);
		$consentRecord->save();
		Craft::$app->response->format = Response::FORMAT_JSON;
		Craft::$app->session->set('cookieConsent', json_encode(array_merge(['consent_uid' => $consentRecord->uid],$consent)));
		return true;
	}
}