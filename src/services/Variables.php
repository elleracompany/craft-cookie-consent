<?php


namespace elleracompany\cookieconsent\services;

use Craft;
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
		$consent = Craft::$app->session->get('cookieConsent');
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
	 * Should we render the banner?
	 *
	 * @return bool
	 */
	public function render() : bool
	{
		return !$this->consent && $this->activated();
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

	public function groups()
	{
		return $this->settings ? $this->settings->cookieGroups : [];
	}

	public function getConsent($slug)
	{
		return isset($this->consent_string->$slug) ? $this->consent_string->$slug == 'on' : false;
	}
}