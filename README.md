# Craft Cookie Consent

A cookie consent banner and consent log for Craft CMS, with built-in Google Consent Mode v2 support.

## Features
- Configurable cookie groups and cookie descriptions per site (multisite compatible)
- Accessible consent banner rendered first in the DOM, with an optional "Accept All" button
- Google Consent Mode v2: map cookie groups to consent signals and let the plugin signal Google tags
- Consent log with configurable retention, ready if you need to prove consent
- Client-side consent state (`window.ccc` + `ccc-loaded` event) that works with static page caches like Blitz
- Granular user permissions for managing settings, groups and consents
- Use the default template, CSS and JS — or replace any of them with your own

## Requirements
Craft CMS 5.1 or later.

## Installation
Install from the [Plugin Store](https://plugins.craftcms.com/cookie-consent), or with composer:

```bash
composer require elleracompany/craft-cookie-consent
./craft plugin/install cookie-consent
```

## Setup
**1. Add the template hook.** The banner is injected through the `after-body-start` hook, so it lands first in the DOM for keyboard and screen-reader users. Add this line right after the opening `<body>` tag in your layout template(s):

```twig
{% hook 'after-body-start' %}
```

**2. Configure your cookie groups.** Navigate to the plugin in the Control Panel and review the cookie groups and cookies for your site — the plugin creates Necessary, Statistics and Marketing groups with sensible defaults for you. Each group has a slug you'll use to check consent in code.

**3. Activate.** Toggle the "Activated" lightswitch in the site settings and you're off.

If your visitors use Google Analytics, Ads or Tag Manager, continue with [Google Consent Mode v2](#google-consent-mode-v2) below.

## Customizing

### Custom template
Point the "Template" field in the site settings to your own template file. It is rendered with `Craft::$app->view->renderTemplate()`. The default template is a good starting point — see `vendor/elleracompany/craft-cookie-consent/src/templates/banner.twig`.

### Custom CSS and JS
Turn off "Load CSS Assets" and/or "Load JS Assets" in the site settings if you ship your own. The plugin's implementation in `vendor/elleracompany/craft-cookie-consent/src/resources` shows what the default assets do (form submission, the `window.ccc` object, Consent Mode updates).

## Checking consent

### In Twig
```twig
{% if craft.cookieConsent.getConsent('default-statistics') %}
    {# render something that requires consent #}
{% endif %}
```

**Note:** if your pages are statically cached (e.g. with Blitz), don't gate scripts server-side with this function — the result gets baked into the cached page. See [Static caching (Blitz)](#static-caching-blitz) for the cache-safe approach.

### In JavaScript
The default script exposes the consent state as `window.ccc` — an object of group slugs and their consent status. It is populated whenever the default JS is loaded and the banner form is present.

A `ccc-loaded` event is dispatched on `window` whenever the consent state has been (re)loaded — both on page load and immediately after the visitor submits or updates their consent. The consent state is available as the event's `detail`, so site scripts can react to consent changes without polling or reloading the page:

```js
window.addEventListener('ccc-loaded', function (event) {
    if (event.detail['default-marketing']) {
        // Load marketing scripts (GTM, pixels, ...) here
    }
});
```

This is the recommended way to gate tracking scripts — it works on statically cached pages, and it applies the moment the visitor accepts, with no reload.

## Google Consent Mode v2
The plugin has built-in support for [Google Consent Mode v2](https://developers.google.com/tag-platform/security/guides/consent), which signals the visitor's consent to Google tags (Analytics, Ads, Tag Manager). Setup:

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

This is Google's "advanced" consent mode: tags load normally and send cookieless pings until consent is granted, which preserves conversion modeling. If you prefer "basic" mode (no Google tags at all before consent), load your tags via the `ccc-loaded` event instead — see [Checking consent](#in-javascript).

The output of `consentModeScript()` contains no visitor-specific data, so it's safe on statically cached pages (see [Static caching (Blitz)](#static-caching-blitz)) — the visitor's actual consent state is always fetched client-side.

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

## Static caching (Blitz)
When pages are cached by Blitz (or any full-page static cache), everything rendered server-side is frozen at cache time — including the banner's visibility and any script conditionally rendered with `craft.cookieConsent.getConsent()`. That means the first visitor's consent state would be served to everyone. Two rules make the plugin work correctly with static caching:

**1. Render the banner through a dynamic include.** Instead of relying on the automatic template hook, disable "Load Template" in the site settings, put `{% include 'cookie-consent/banner' %}` in a small template of its own (e.g. `_includes/cookie-consent.twig`), and include it dynamically so it's rendered fresh per visitor while the rest of the page stays cached:

```twig
{{ craft.blitz.includeDynamic('_includes/cookie-consent') }}
```

**2. Load consent-gated scripts client-side, not in Twig.** Don't wrap tracking scripts (GTM, analytics, pixels — including SEOmatic's script container) in `craft.cookieConsent.getConsent()` conditions on cached pages. Use the `ccc-loaded` event (see [Checking consent](#in-javascript)) or the built-in [Consent Mode support](#google-consent-mode-v2) instead — both apply the visitor's actual consent client-side, with no reload or cache clearing needed.

The banner itself is cache-friendly: the CSRF token is not rendered into the page but fetched via ajax when consent is submitted, so cached pages stay valid for every visitor.

## Embedding the consent form on a specific page
The plugin's templates are registered as site templates, so you can embed the consent form directly in any of your own templates — for example on a privacy or cookie policy page where visitors should be able to review and update their consent:

```twig
{% include 'cookie-consent/banner' %}
```

If you only want the consent form on specific pages, disable "Load Template" in the site settings so the floating banner isn't injected on every page. Note that the plugin's JS/CSS assets are only loaded automatically while the banner would be shown — if you've disabled "Show after Consent", register the asset bundles yourself in the template where you embed the form:

```twig
{% do view.registerAssetBundle('elleracompany\\cookieconsent\\JSAssets') %}
{% do view.registerAssetBundle('elleracompany\\cookieconsent\\CSSAssets') %}
```

Don't embed the form on a page where the floating banner is also rendered — the two copies would share element IDs.

## The consent log
Every consent a visitor gives is stored in the database with a UID, so you can prove consent if required. Browse the log under "View consents" in the plugin's CP section. The visitor's IP address is only stored when they accept a group with "Store IP" enabled.

"Invalidate Consents" in the site settings invalidates all stored consents at once, prompting every visitor to consent again — useful after changing your cookies or groups.

### Cleaning up old consents
Use the console command to clear old consents from the database, e.g. on a nightly cron:

```bash
./craft cookie-consent/retention/clear
```

| Option | Alias | Default | Type | Description |
|---|---|---|---|---|
| --days | -d | 365 | Integer | Number of days to keep records |
| --sid | -s | null | Integer | Only clear consents from the site with this ID |
| --handle | -h | null | String | Only clear consents from the site with this handle |

**Note:** pass either *sid* or *handle* — not both. If no site is specified, consents are deleted from all sites.

Example: delete all consents older than 2 years from site with ID 1

```bash
./craft cookie-consent/retention/clear -s 1 -d 730
```

## User permissions
The plugin registers granular permissions under "Cookie Banner" in Craft's user permission settings: activating the banner, changing the template, updating content, viewing consents, and creating/editing/deleting cookie groups. On multisite installs, site edit permissions are respected as well.

## Acknowledgements
Plugin Icon designed by Trinh Ho from Flaticon
