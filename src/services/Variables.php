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
	 * @var string|null
	 */
	private $consent;

	/**
	 * Initiate the plugin for this site
	 */
	public function init()
	{
		$this->settings = SiteSettings::find()->where(['site_id' => Craft::$app->getSites()->currentSite->id])->with('cookieGroups')->one();
		$this->consent = Craft::$app->session->get('cookieConsent');
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
}