<?php


namespace elleracompany\cookieconsent\services;

use Craft;
use craft\helpers\Db;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\base\Component;

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

		if(isset($this->settings->activated) && $this->settings->activated == 1)
        {
            $consent = Craft::$app->request->cookies->getValue($this->settings->cookieName);

            $this->consent_string = $consent !== null ? json_decode($consent) : null;
            $this->consent = is_object($this->consent_string) && isset($this->consent_string->consent_uid);

            if($this->consent)
            {
                $consentRecord = Consent::findOne($this->consent_string->consent_uid);
                if(!$consentRecord || strtotime($consentRecord->dateUpdated) < strtotime($this->settings->dateInvalidated)) {
                    $this->consent = false;
                    $this->consent_string = null;
                }
            }

        }

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

    public function showAcceptAllButton()
    {
        return (bool) $this->settings->acceptAllButton;
    }

	public function getUid()
	{
		return $this->consent_string->consent_uid;
	}

	public function getConsent($slug)
	{
		if (isset($this->consent_string->$slug)) {
			// Cookies written since 5.x store booleans; 'on' covers cookies from older versions
			return $this->consent_string->$slug === true || $this->consent_string->$slug === 'on';
		}
		return in_array($slug, $this->settings->getRequiredCookieGroups());
	}

    public function getConsents()
    {
        return $this->consent_string;
    }

	public function getRefreshDataAttr()
    {
        if($this->settings->refresh) return "data-refresh=true data-refreshTime={$this->settings->refresh_time}";
        return "data-refresh=false";
    }
}