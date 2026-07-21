<?php

namespace elleracompany\cookieconsent\services;

use Craft;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use elleracompany\cookieconsent\CookieConsent;
use elleracompany\cookieconsent\records\Consent;
use elleracompany\cookieconsent\records\SiteSettings;
use Twig\Markup;
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
    private ?object $consentData = null;

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
            $this->consentData = is_object($decoded) ? $decoded : null;
            $this->consent = isset($this->consentData->consent_uid);

            if ($this->consent) {
                $consentRecord = Consent::findOne($this->consentData->consent_uid);
                if (!$consentRecord || strtotime($consentRecord->dateUpdated) < strtotime($this->settings->dateInvalidated)) {
                    $this->consent = false;
                    $this->consentData = null;
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
        return $this->consentData->consent_uid ?? null;
    }

    public function getConsent(string $slug): bool
    {
        if (isset($this->consentData->$slug)) {
            // Cookies written since 5.x store booleans; 'on' covers cookies from older versions
            return $this->consentData->$slug === true || $this->consentData->$slug === 'on';
        }
        return $this->settings ? in_array($slug, $this->settings->getRequiredCookieGroups(), true) : false;
    }

    public function getConsents(): ?object
    {
        return $this->consentData;
    }

    /**
     * Is Google Consent Mode v2 enabled (and the banner activated) for this site?
     */
    public function consentModeEnabled(): bool
    {
        return (bool) ($this->settings?->activated && $this->settings->enableConsentMode);
    }

    /**
     * Map of cookie group slug to the Google Consent Mode signals the group grants
     *
     * @return array<string,string[]>
     */
    public function getGcmMap(): array
    {
        $map = [];
        if (!$this->settings) {
            return $map;
        }
        foreach ($this->settings->cookieGroups as $group) {
            $signals = $group->getSignals();
            if ($signals !== []) {
                $map[$group->slug] = $signals;
            }
        }
        return $map;
    }

    /**
     * The default consent state for gtag('consent', 'default', ...): the four
     * core v2 signals plus every mapped signal denied, except signals granted
     * by required groups. Contains no visitor-specific data, so pages using
     * it stay cacheable.
     *
     * @return array<string,string>
     */
    public function getGcmDefaults(): array
    {
        $defaults = [];
        foreach (CookieConsent::GCM_CORE_SIGNALS as $signal) {
            $defaults[$signal] = 'denied';
        }
        if (!$this->settings) {
            return $defaults;
        }
        foreach ($this->settings->cookieGroups as $group) {
            foreach ($group->getSignals() as $signal) {
                if ($group->required) {
                    $defaults[$signal] = 'granted';
                } elseif (!isset($defaults[$signal])) {
                    $defaults[$signal] = 'denied';
                }
            }
        }
        return $defaults;
    }

    /**
     * JSON consumed by the banner script (data-gcm attribute): the group to
     * signal map and the data layer name
     */
    public function getGcmJson(): string
    {
        return json_encode([
            'dataLayer' => $this->settings->gcmDataLayerName ?: 'dataLayer',
            'map' => (object) $this->getGcmMap(),
        ]);
    }

    /**
     * Inline script tag for Google Consent Mode v2. Place it in the <head>,
     * before the gtag.js/GTM snippet (including snippets rendered by other
     * plugins such as SEOmatic). Sets the default (denied) consent state
     * synchronously, then fetches the visitor's stored consent and pushes a
     * consent update. The output contains no visitor-specific data.
     */
    public function consentModeScript(): Markup
    {
        if (!$this->consentModeEnabled()) {
            return Template::raw('');
        }

        $dataLayer = json_encode($this->settings->gcmDataLayerName ?: 'dataLayer');
        $defaults = $this->getGcmDefaults();
        if ((int) $this->settings->gcmWaitForUpdate > 0) {
            $defaults['wait_for_update'] = (int) $this->settings->gcmWaitForUpdate;
        }
        $map = json_encode((object) $this->getGcmMap());
        $showUrl = json_encode(UrlHelper::siteUrl('cookie-consent/show'));

        $js = "window[{$dataLayer}] = window[{$dataLayer}] || [];\n";
        $js .= "function gtag(){window[{$dataLayer}].push(arguments);}\n";
        $js .= "gtag('consent', 'default', ".json_encode($defaults).");\n";
        if ($this->settings->gcmAdsDataRedaction) {
            $js .= "gtag('set', 'ads_data_redaction', true);\n";
        }
        if ($this->settings->gcmUrlPassthrough) {
            $js .= "gtag('set', 'url_passthrough', true);\n";
        }
        // Fetch the visitor's stored consent and lift the defaults. This is
        // independent of the banner script so the update also happens on
        // pages where the banner is not rendered (e.g. consent already given
        // and "Show after Consent" disabled).
        $js .= "fetch({$showUrl}, {headers: {'Accept': 'application/json'}})\n";
        $js .= "    .then(function (r) { return r.ok ? r.json() : null; })\n";
        $js .= "    .then(function (c) {\n";
        $js .= "        if (!c || typeof c.consent_uid === 'undefined') { return; }\n";
        $js .= "        var map = {$map}, state = {};\n";
        $js .= "        for (var slug in map) {\n";
        $js .= "            var granted = c[slug] === true || c[slug] === 'on';\n";
        $js .= "            for (var i = 0; i < map[slug].length; i++) {\n";
        $js .= "                if (granted || state[map[slug][i]] !== 'granted') {\n";
        $js .= "                    state[map[slug][i]] = granted ? 'granted' : 'denied';\n";
        $js .= "                }\n";
        $js .= "            }\n";
        $js .= "        }\n";
        $js .= "        if (Object.keys(state).length) { gtag('consent', 'update', state); }\n";
        $js .= "    })\n";
        $js .= "    .catch(function () {});\n";

        return Template::raw("<script>\n".$js."</script>");
    }

    public function refreshEnabled(): bool
    {
        return (bool) $this->settings?->refresh;
    }

    public function refreshTime(): int
    {
        return (int) ($this->settings->refresh_time ?? 0);
    }

    /**
     * @deprecated Use refreshEnabled() and refreshTime() and build the attributes in the template instead.
     */
    public function getRefreshDataAttr(): string
    {
        if ($this->refreshEnabled()) {
            return "data-refresh=true data-refreshTime={$this->refreshTime()}";
        }
        return "data-refresh=false";
    }
}
