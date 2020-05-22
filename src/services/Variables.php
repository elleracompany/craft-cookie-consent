<?php


namespace elleracompany\cookieconsent\services;

use Craft;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\base\Component;
use yii\web\Cookie;

class Variables extends Component
{
	/**
	 * Current site settings
	 * @var SiteSettings|null
	 */
	private $settings;

	/**
	 * User consent
	 * @var bool
	 */
	private $consent;

	/**
	 * User consent content
	 * @var string
	 */
	private $consent_string;

	/**
	 * Initiate the plugin for this site
	 */
	public function init()
	{
		$this->settings = SiteSettings::find()->where(['site_id' => Craft::$app->getSites()->currentSite->id])->with('cookieGroups')->one();

        $consent = Craft::$app->session->get('cookieConsent');

        if($consent)
        {
            $cookie = new Cookie([
                'name' => $this->settings->cookieName,
                'value' => $consent,
                'expire' => strtotime('+1 year', time())
            ]);

            Craft::$app->response->cookies->add($cookie);
        }
		else $consent = Craft::$app->request->cookies->get($this->settings->cookieName);

		$this->consent = $consent !== null;
		$this->consent_string = json_decode($consent);

		parent::init();
	}

	/**
	 * Is the banner active on this site?
	 *
	 * @return bool
	 */
	public function activated() : bool
	{
		return is_object($this->settings) && isset($this->settings->activated) ? (bool) $this->settings->activated : false;
	}

	/**
	 * Should we render anything?
	 *
	 * @return bool
	 */
	public function render() : bool
	{
		return $this->activated() && (!$this->consent || $this->settings->showAfterConsent);
	}

	/**
	 * Should we render anything?
	 *
	 * @return bool
	 */
	public function hideCheckboxes() : bool
	{
		return ! (bool) $this->settings->showCheckboxes;
	}

	/**
	 * Should we render the banner?
	 *
	 * @return bool
	 */
	public function renderTemplate() : bool
	{
		return $this->settings->templateAsset == 1;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function description() : string
	{
		return $this->settings->description;
	}
	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function updated() : string
	{
		return $this->settings->getLastUpdate();
	}

	/**
	 * Get Headline
	 *
	 * @return string
	 */
	public function headline() : string
	{
		return $this->settings->headline;
	}

	public function getTemplate() : string
	{
		return $this->settings->template;
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function loadCss()
	{
		if($this->settings->cssAssets) Craft::$app->view->registerAssetBundle("elleracompany\\cookieconsent\\CSSAssets");
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function loadJs()
	{
		if($this->settings->jsAssets) Craft::$app->view->registerAssetBundle("elleracompany\\cookieconsent\\JSAssets");
	}

	public function groups()
	{
		return $this->settings ? $this->settings->cookieGroups : [];
	}

	public function consentGiven()
	{
		return $this->consent;
	}

	public function showAfterConsent()
	{
		return (bool) $this->settings->showAfterConsent;
	}

	public function getUid()
	{
		return $this->consent_string->consent_uid;
	}

	public function getConsent($slug)
	{
		return isset($this->consent_string->$slug) ? $this->consent_string->$slug == 'on' : in_array($slug, $this->settings->getRequiredCookieGroups());
	}
}