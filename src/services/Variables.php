<?php

namespace elleracompany\cookieconsent\services;

use Craft;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;
use yii\base\Component;

class Variables extends Component
{
	/**
	 * Current site settings
	 */
	private ?SiteSettings $settings = null;

	/**
	 * User consent
	 */
	private bool $consent = false;

	/**
	 * Decoded consent cookie payload
	 */
	private ?object $consent_string = null;

	/**
	 * Initiate the plugin for this site
	 */
	public function init(): void
	{
		/** @var SiteSettings|null $settings */
		$settings = SiteSettings::find()->where(['site_id' => Craft::$app->getSites()->currentSite->id])->with('cookieGroups')->one();
		$this->settings = $settings;

		if ($this->settings && $this->settings->activated) {
			$cookie = Craft::$app->request->cookies->getValue($this->settings->cookieName);

			$decoded = $cookie !== null ? json_decode($cookie) : null;
			$this->consent_string = is_object($decoded) ? $decoded : null;
			$this->consent = isset($this->consent_string->consent_uid);

			if ($this->consent) {
				$consentRecord = Consent::findOne($this->consent_string->consent_uid);
				if (!$consentRecord || strtotime($consentRecord->dateUpdated) < strtotime($this->settings->dateInvalidated)) {
					$this->consent = false;
					$this->consent_string = null;
				}
			}
		}

		parent::init();
	}

	/**
	 * Is the banner active on this site?
	 */
	public function activated(): bool
	{
		return (bool) $this->settings?->activated;
	}

	/**
	 * Should we render anything?
	 */
	public function render(): bool
	{
		return $this->activated() && (!$this->consent || $this->settings->showAfterConsent);
	}

	/**
	 * Should the checkboxes be hidden until "Details" is opened?
	 */
	public function hideCheckboxes(): bool
	{
		return $this->settings ? !$this->settings->showCheckboxes : false;
	}

	/**
	 * Should we render the banner?
	 */
	public function renderTemplate(): bool
	{
		return (bool) $this->settings?->templateAsset;
	}

	/**
	 * Get Description
	 */
	public function description(): string
	{
		return $this->settings->description ?? CookieConsent::DEFAULT_DESCRIPTION;
	}

	/**
	 * Get the timestamp of the last settings/group update
	 */
	public function updated(): ?int
	{
		return $this->settings?->getLastUpdate();
	}

	/**
	 * Get Headline
	 */
	public function headline(): string
	{
		return $this->settings->headline ?? CookieConsent::DEFAULT_HEADLINE;
	}

	public function getTemplate(): string
	{
		return $this->settings->template ?? CookieConsent::DEFAULT_TEMPLATE;
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function loadCss(): void
	{
		if ($this->settings?->cssAssets) {
			Craft::$app->view->registerAssetBundle(\elleracompany\cookieconsent\CSSAssets::class);
		}
	}

	/**
	 * @throws \yii\base\InvalidConfigException
	 */
	public function loadJs(): void
	{
		if ($this->settings?->jsAssets) {
			Craft::$app->view->registerAssetBundle(\elleracompany\cookieconsent\JSAssets::class);
		}
	}

	public function groups(): array
	{
		return $this->settings ? $this->settings->cookieGroups : [];
	}

	public function consentGiven(): bool
	{
		return $this->consent;
	}

	public function showAfterConsent(): bool
	{
		return (bool) $this->settings?->showAfterConsent;
	}

	public function showAcceptAllButton(): bool
	{
		return (bool) $this->settings?->acceptAllButton;
	}

	public function getUid(): ?string
	{
		return $this->consent_string->consent_uid ?? null;
	}

	public function getConsent($slug): bool
	{
		if (isset($this->consent_string->$slug)) {
			// Cookies written since 5.x store booleans; 'on' covers cookies from older versions
			return $this->consent_string->$slug === true || $this->consent_string->$slug === 'on';
		}
		return $this->settings ? in_array($slug, $this->settings->getRequiredCookieGroups()) : false;
	}

	public function getConsents(): ?object
	{
		return $this->consent_string;
	}

	public function getRefreshDataAttr(): string
	{
		if ($this->settings?->refresh) {
			return "data-refresh=true data-refreshTime={$this->settings->refresh_time}";
		}
		return "data-refresh=false";
	}
}
