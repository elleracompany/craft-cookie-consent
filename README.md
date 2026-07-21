# Craft Cookie Consent

Ellera Cookie Consent Plugin

## Install the plugin
You can install this plugin from the Plugin Store.

## Create hook in your template
To give you full control over where the plugin is rendering the consent template. The cookie banner should be the first thing in the DOM for keyboard and screen-reader users. 

You need to add this line right after your start-body-tag in the relevant layout file(s):

`{% hook 'after-body-start' %}`

## Activate the plugin for your site
Navigate to the plugin inside your Control Panel. If you have the correct permissions, it should be visible in the menu.

Go to "Site Settings" and add your cookies and cookie groups (We've added some basic ones for you already).

Toggle the "Activated" lightswitch and you're off!

## Customize

### Using a custom template
Update the template file under Site Settings to point to your template file.
The path is rendered with `Craft::$app->view->renderTemplate()`.

### Using custom CSS and/or JS
You can turn off loading of assets in Site Settings if you have your own.

You can look in `vendor/elleracompany/craft-cookie-consent/src/resources` to see the functionality currently implemented by the plugin.

## Check for user consent
You can use this function to manage cookie generation for your scripts

`{% if craft.cookieConsent.getConsent('slug') %}`

You can use it in SEOmatic by navigating to `admin/seomatic/tracking` and updating the script template.

**Note:** if your pages are statically cached (e.g. with Blitz), don't gate scripts server-side with this function — the result gets baked into the cached page. See [Static caching (Blitz)](#static-caching-blitz) below for the cache-safe approach.

## Planned features
- Project.yaml compatibility

## Example using SEOmatic

**Note:** this server-side approach is not suitable for statically cached sites — if you use Blitz or similar, see [Static caching (Blitz)](#static-caching-blitz) instead.

You will need to bust the SEOmatic cache in your twig templates (not in the SEOmatic script field)
```
{# -- START Cache-bust seomatic -- #}
{% set scriptContainer = seomatic.script.container() %}
{% do scriptContainer.clearCache(true) %}
{# -- END Cache-bust seomatic -- #}
```
After that you can update the script field inside SEOmatic to something like this:
```
{% if trackingId.value is defined and trackingId.value %}
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','{{ analyticsUrl.value }}','ga');
ga('create', '{{ trackingId.value |raw }}', 'auto'{% if linker.value %}, {allowLinker: true}{% endif %});
{% if ipAnonymization.value or craft.cookieConsent.getConsent('default-marketing') != true %}
ga('set', 'anonymizeIp', true);
{% endif %}
{% if displayFeatures.value and craft.cookieConsent.getConsent('default-marketing') %}
ga('require', 'displayfeatures');
{% endif %}
{% if ecommerce.value and craft.cookieConsent.getConsent('default-marketing') %}
ga('require', 'ecommerce');
{% endif %}
{% if enhancedEcommerce.value and craft.cookieConsent.getConsent('default-marketing') %}
ga('require', 'ec');
{% endif %}
{% if enhancedLinkAttribution.value and craft.cookieConsent.getConsent('default-marketing') %}
ga('require', 'linkid');
{% endif %}
{% if enhancedLinkAttribution.value and craft.cookieConsent.getConsent('default-marketing') %}
ga('require', 'linker');
{% endif %}
{% set pageView = (sendPageView.value and not craft.app.request.isLivePreview) %}
{% if pageView %}
ga('send', 'pageview');
{% endif %}
{% endif %}
```

## Cleaning up old consents
You can use a console command to clear out old consents from the database.
```bash
./craft cookie-consent/retention/clear
```

This command has three optional parameters:

| Parameter | Alternative | Default | Type | Description |
|---|---|---|---|---|
| -d # | --days # | 365 | Integer | Sets number of days to keep records |
| -s # | --sid # | null | Integer | Pass a site ID to only clear consents from that site |
| -s <handle> | --handle <handle> | null | String | Pass a site handle to only clear consents from that site |

**note:** you can only pass *sid* or *handle* - not both. If a site is not specified, consents will be deleted from all sites.

Example: Delete all consents older than 2 years from site with ID 1
```bash
./craft cookie-consent/retention/clear -sid 1 -d 730
```

## The window.ccc object
As of version 1.6.0, a new object window.ccc has been added to the default template.

This object contains key-value pairs of consents and their status.

The object will be created if the default javascript file is loaded, and a form with the ID #elc-cookie-consent-form exists and has the `data-show`-attribute has the url to the consent show endpoint(/cookie-consent/show)

As of version 5.1.0, a `ccc-loaded` event is dispatched on `window` whenever the consent state has been (re)loaded — both on page load and after the visitor submits or updates their consent. The consent state is available as the event's `detail`, so site scripts can react to consent without polling or reloading the page:

```js
window.addEventListener('ccc-loaded', function (event) {
    if (event.detail['marketing']) {
        // Load marketing scripts (GTM, pixels, ...) here
    }
});
```

This is especially useful together with static page caches (e.g. Blitz), where conditionally rendering scripts server-side with `craft.cookieConsent.getConsent()` would get baked into the cached page.

## Static caching (Blitz)
When pages are cached by Blitz (or any full-page static cache), everything rendered server-side is frozen at cache time — including the banner's visibility and any script conditionally rendered with `craft.cookieConsent.getConsent()`. That means the first visitor's consent state would be served to everyone. Two rules make the plugin work correctly with static caching:

**1. Render the banner through a dynamic include.** Instead of relying on the automatic template hook, disable "Load Template" in the site settings, put `{% include 'cookie-consent/banner' %}` in a small template of its own (e.g. `_includes/cookie-consent.twig`), and include it dynamically so it's rendered fresh per visitor while the rest of the page stays cached:

```twig
{{ craft.blitz.includeDynamic('_includes/cookie-consent') }}
```

**2. Load consent-gated scripts client-side, not in Twig.** Don't wrap tracking scripts (GTM, analytics, pixels — including SEOmatic's script container) in `craft.cookieConsent.getConsent()` conditions on cached pages. Use the `ccc-loaded` event instead, which fires on page load and again immediately after the visitor accepts or updates their consent — no reload or cache clearing needed:

```js
window.addEventListener('ccc-loaded', function (event) {
    if (event.detail['default-marketing']) {
        // Inject GTM / marketing scripts here
    }
    if (event.detail['default-statistics']) {
        // Inject analytics here
    }
});
```

The banner itself is cache-friendly as of 5.1.0: the CSRF token is no longer rendered into the page but fetched via ajax when consent is submitted, so cached pages stay valid for every visitor.

## Google Consent Mode v2
As of version 5.2.0 the plugin has built-in support for [Google Consent Mode v2](https://developers.google.com/tag-platform/security/guides/consent), which signals the visitor's consent to Google tags (Analytics, Ads, Tag Manager). Setup:

**1. Map your cookie groups to consent signals.** On each cookie group's settings page, check the Google Consent Mode signals the group grants when accepted. Typically:

| Cookie group | Signals |
| --- | --- |
| Necessary | `functionality_storage`, `security_storage` |
| Statistics | `analytics_storage` |
| Marketing | `ad_storage`, `ad_user_data`, `ad_personalization` |

New installs get these mappings on the default groups automatically.

**2. Enable Consent Mode** in the plugin's site settings.

**3. Place the consent mode script in your layout's `<head>`, before any Google tags:**

```twig
<head>
    {{ craft.cookieConsent.consentModeScript() }}
    {# gtag.js / GTM snippets load below this line #}
    ...
</head>
```

The script sets the default consent state (everything denied, except signals granted by *required* groups) synchronously, so Google tags that load afterwards hold back cookies until consent is known. It then fetches the visitor's stored consent and pushes a `consent update`. When the visitor submits or changes their consent in the banner, an update is pushed immediately — no reload needed.

This is Google's "advanced" consent mode: tags load normally and send cookieless pings until consent is granted, which preserves conversion modeling. If you prefer "basic" mode (no Google tags at all before consent), load your tags via the `ccc-loaded` event instead — see [The window.ccc object](#the-windowccc-object).

The output of `consentModeScript()` contains no visitor-specific data, so it's safe on statically cached pages (see the Blitz section above) — the visitor's actual consent state is always fetched client-side.

**Options** (site settings):
- **Data layer name** — must match the data layer variable your Google tags use. Leave as `dataLayer` unless you renamed it.
- **Wait for update** — how long Google tags wait for the consent state before firing (default 2000 ms).
- **Redact ads data** — sets `ads_data_redaction`, redacting ad click identifiers while `ad_storage` is denied.
- **URL passthrough** — sets `url_passthrough`, passing ad click info through URLs while `ad_storage` is denied.

### With SEOmatic (or other plugins rendering Google tags)
SEOmatic injects its tracking scripts right before the closing `</head>` tag, so placing `{{ craft.cookieConsent.consentModeScript() }}` at the top of your `<head>` guarantees the default consent state is set first. No SEOmatic configuration is needed — its gtag/GTM scripts pick up the consent state automatically. If you've renamed the data layer variable in SEOmatic's GTM settings, set the same name in this plugin's site settings.

### Manual implementation
If you need full control (custom signals, regional defaults, ...), leave "Enable Google Consent Mode" off and wire it up yourself with the `ccc-loaded` event:

```html
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    // Deny everything until the visitor has answered
    gtag('consent', 'default', {
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'analytics_storage': 'denied'
    });

    window.addEventListener('ccc-loaded', function (event) {
        gtag('consent', 'update', {
            'ad_storage': event.detail['default-marketing'] ? 'granted' : 'denied',
            'ad_user_data': event.detail['default-marketing'] ? 'granted' : 'denied',
            'ad_personalization': event.detail['default-marketing'] ? 'granted' : 'denied',
            'analytics_storage': event.detail['default-statistics'] ? 'granted' : 'denied'
        });
    });
</script>
```

Adjust the group slugs (`default-marketing`, `default-statistics`) to match your cookie groups — you'll find the slug on each group's settings page.

## Embedding the consent form on a specific page
As of version 5.1.0 the plugin's templates are registered as site templates, so you can embed the consent form directly in any of your own templates — for example on a privacy or cookie policy page where visitors should be able to review and update their consent:

```twig
{% include 'cookie-consent/banner' %}
```

If you only want the consent form on specific pages, disable "Load Template" in the site settings so the floating banner isn't injected on every page. Note that the plugin's JS/CSS assets are only loaded automatically while the banner would be shown — if you've disabled "Show after Consent", register the asset bundles yourself in the template where you embed the form:

```twig
{% do view.registerAssetBundle('elleracompany\\cookieconsent\\JSAssets') %}
{% do view.registerAssetBundle('elleracompany\\cookieconsent\\CSSAssets') %}
```

Don't embed the form on a page where the floating banner is also rendered — the two copies would share element IDs.

## Acknowledgements
Plugin Icon designed by Trinh Ho from Flaticon
