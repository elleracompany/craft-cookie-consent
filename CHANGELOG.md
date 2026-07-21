# Release Notes for Cookie Consent

## 5.1.0 - 2026-07-21

### Improved
- The banner script now dispatches a `ccc-loaded` event on `window` whenever the consent state has been loaded or updated, with the consent state as the event `detail` ([#118](https://github.com/elleracompany/craft-cookie-consent/issues/118)). This lets site scripts react to consent changes (e.g. loading GTM after acceptance) without polling `window.ccc` or reloading the page — see the README for an example.
- CP save/delete/invalidate actions now use Craft's `asSuccess()`/`asFailure()`/`asModelSuccess()`/`asModelFailure()` response helpers. Saving site settings now redirects after success (preventing form re-submission on refresh) and stays on the selected site, and a failed cookie group deletion now shows an error instead of silently redirecting.
- The front-end banner bootstrapping (settings lookup, asset loading, template hook) is now deferred with `Craft::$app->onInit()` as recommended by the Craft plugin guide, instead of querying the database during plugin initialization.
- Added a database index on the consent table (`site_id`, `dateUpdated`), speeding up the CP consent list and the retention command on large tables (schema version 1.6.0).
- The banner form's refresh settings are now rendered as proper quoted `data-refresh`/`data-refreshtime` attributes via the new `craft.cookieConsent.refreshEnabled` and `craft.cookieConsent.refreshTime` variables. `getRefreshDataAttr()` (which returned a raw unescaped HTML string) is deprecated but still works for custom templates.
- Modernized the banner script: removed IE8-era `attachEvent` fallbacks and the hand-rolled form serializer, replaced `XMLHttpRequest` with `fetch()`, merged the duplicated save/accept-all submit paths, and errors are now reported with `console.error`. Loading the JS asset without the banner template no longer throws. The script now requires a browser with `fetch` support (all browsers since ~2017).
- The `cookie-consent/retention/clear` console command now deletes consents with a single query instead of loading every record into memory, returns proper exit codes, writes errors to stderr, and aborts with an error when `--sid` or `--handle` doesn't match a site. Previously an unknown site was silently ignored and consents were cleared for **all** sites.
- The consent submission endpoint now requires a POST request, validates `site_id`, returns a JSON object (`success`, `uid`, `consent`) instead of a bare `true`, and sets `SameSite=Lax` on the consent cookie. Submitting for a site without settings now returns a 400 response instead of causing a PHP error.

### Internal
- Added recommended composer.json metadata: plugin description, developer name/URL/email and issue tracker/docs support links.
- Site-permission and required-group lookups now use strict `in_array()` comparisons per the Craft coding guidelines, and `getConsent()` requires a string slug.
- PHPStan level raised from 0 to 4 and the codebase passes cleanly; relation magic properties are now documented with `@property-read` annotations.
- The CP Readme page now resolves the README through the plugin's base path and no longer errors if the file is missing.
- Normalized code style across the plugin: consistent 4-space indentation (tabs removed), braces on all conditionals, and an `.editorconfig` added.
- Added parameter and return type declarations throughout (fixing PHP 8.4 implicit-nullable deprecation warnings), replaced the deprecated `SluggableBehavior::className()` call and string class names with `::class` constants, and corrected copy-pasted docblocks in the record classes. `SiteSettings::getLastUpdate()` no longer errors when there is nothing to compare.
- Removed dead code: a large commented-out block and no-op request calls in the settings controller, an unused variable in the site permission check, leftover TODO stubs and empty asset arrays.

### Removed
- Removed the unreachable `settings/retention` CP action: it had no route and referenced a template that never existed. Consent retention is handled by the `cookie-consent/retention/clear` console command (see README).

### Security
- "Invalidate Consents" now requires a POST request with a CSRF token and the `cookie-consent:site-settings` permission (plus site edit permission on multisite installs). Previously any logged-in CP user could invalidate all consents via a GET request. The button now asks for confirmation, and an unknown site no longer causes a PHP error.

### Bugfix
- If the banner form is submitted natively (JS disabled or not yet loaded — seen on some iOS devices), the consent endpoint now redirects back to the originating page instead of rendering a raw response ([#116](https://github.com/elleracompany/craft-cookie-consent/issues/116)). The banner script also explicitly requests JSON.
- Validation errors are now displayed when saving a cookie group fails ([#96](https://github.com/elleracompany/craft-cookie-consent/issues/96)). A strict int/string comparison on `site_id` made the edit page redirect to the Readme after a failed save, discarding the errors. The Name and Description fields are also marked as required in the form.
- The "Last updated" date shown by `craft.cookieConsent.updated` now takes cookie group changes into account as intended. The previous code iterated over the relation query object instead of the related records, so group updates never affected the date.
- The "Required", "Name", "Description", "Provider" and "Expiry" strings in the banner template were translated with the `site` category instead of `cookie-consent`, so the plugin's bundled translations (e.g. Norwegian) were never used for them.
- Translation overhaul ([#82](https://github.com/elleracompany/craft-cookie-consent/issues/82)): all remaining hardcoded CP strings are now translatable ("Last invalidated:", the slug-change warning, the consent list pagination), the translation files now cover every string the plugin uses — around 30 missing keys added, including "Accept All" which had no Norwegian translation — and stale keys were removed.
- The IP column in the consent list now renders its status indicator correctly instead of printing escaped HTML.
- The banner no longer errors when a cookie group has no cookies JSON stored.
- An unknown `?site=` handle in the CP settings pages now falls back to the primary site instead of causing a PHP error.
- Consent list pagination links are now generated with `UrlHelper::cpUrl()` and keep the selected site. Previously they were built by string concatenation (broken with a custom `cpTrigger` setup or subdirectory installs) and always jumped back to the primary site on multisite installs.
- Using `craft.cookieConsent.*` template variables on a site that has no Cookie Consent settings row (or a tampered/non-JSON consent cookie) no longer causes a PHP error. Every variable now falls back to a sensible default when settings are missing.
- `craft.cookieConsent.getConsent()` compared stored consent values against the string `'on'` even though the consent cookie stores booleans; it only returned the right result through PHP's loose comparison rules. It now checks booleans strictly (still accepting `'on'` from cookies set by very old versions).
- Activating the banner made every front-end page uncacheable: `{{ csrfInput() }}` in the banner template caused Craft to overwrite the response's cache headers with no-cache headers, and the plugin opened a PHP session (and re-sent the consent cookie) on every request. The CSRF token is now fetched via ajax when consent is submitted, and the consent state is read from the request cookie only.

### Breaking
- `{{ csrfInput() }}` was removed from the banner template. If you use a custom template based on the old one, remove `{{ csrfInput() }}` and add `data-csrf="{{ actionUrl('cookie-consent/consent/csrf') }}"` to the form tag to get the same caching fix. Custom templates that keep `{{ csrfInput() }}` continue to work, but their pages remain uncacheable.

## 5.0.4 - 2024-11-07

### Updated
-  Dependabot - dependency vulnerability alert (composer.lock). Dependencies in composer.json seems ok, no need for a .lock file so that was removed.

## 5.0.3 - 2024-10-22

### Enhancement
- Set secure flag for cookie [#101](https://github.com/elleracompany/craft-cookie-consent/issues/101)(@white-lukas)

## 5.0.2 - 2024-09-09

### Bugfix
- Permission slug mismatch / typo (cookie-consent:cookie-groups:create-new) (#112)

## 5.0.1 - 2024-05-02

### Bugfix
- Wrong redirect URL after deletion of cookie group (#109)

## 5.0.0 - 2024-04-19

### Deprecated
- before-body-end hook deprecated. Use after-body-start.

## 4.0.4 - 2022-09-30

### Enhancement
- Added add, reorder and delete on cookie table (PR #93 from mikewink), fixing #90 and #91.

## 4.0.3 - 2022-06-21

### Enhancement
- Switching between sites in multisite installs no longer sends you to the readme-page (#85)

## 4.0.2 - 2022-06-21

### Bugfix
- Updated permissions to Craft 4 syntax (#86)

## 4.0.1 - 2022-05-27

### Bugfix
- Updated to match new Craft4 multisite cp Routing

## 4.0.0 - 2022-05-23

### Updated
- Now supports Craft 4

## 1.6.1 - 2021-10-18

### Bugfixes
- Fixed javascript for custom templates where some elements are missing (#56)

## 1.6.0 - 2021-09-28

### Added
- Added ability to modify slug of cookie groups (#68)
- Added a javascript object window.ccc that contains all consents for a user (#65)

### Enhancement
- Changed the heading from H1 to H3 in the default template (#67)

### Bugfixes
- Made refresh_time default to 500ms if left blank (#66)

## 1.5.0 - 2021-07-08

### Added
- Added invalidation logic. TODO: Permissions

### Enhancement
- Added hooks for after-body-start to reflect the best position for the template rendering. before-body-end will still work as of this version. (#52)

### Bugfixes
- Updated the ip column in consents to 39 characters to allow IPv6 (#51)
- Removed duplicate settings field (@mikewink)
- Added missing translations (@mikewink)

## 1.4.6 - 2021-02-24

### Bugfix
- Fixed a typo that made install migration fail in some versions of PHP (#63)

## 1.4.5 - 2021-01-27

### Bugfix
- Updated the migrations to add primary keys on table creation. (#58)

## 1.4.4 - 2020-07-22

### Bugfix
- Fixed a bug in the consent-checker where it used some unset settings before the plugin were activated. (#38)
- Visiting the readme page now retains the current site you're on. (#39)
- Fixed the views to not default to default site when user lacks access to default site. (#42)

## 1.4.3 - 2020-07-15

### Bugfix
- Fixed a type in the last migration resulting in failed migration on postgres (#40)

### Enhancement
- Updated README.md

## 1.4.2 - 2020-06-19

### Enhancement
- Added option to automatically refresh the page when users accept or update their consent.

## 1.4.1 - 2020-06-10

### Bugfix
- Fixed a bug in the admin consent view when adding cookie groups after consents have been given (#35)

## 1.4.0 - 2020-06-08

### Enhancement
- Added order field for cookie groups (#28)
- Opening expanded cookie banner when revisiting consent (#24)

## 1.3.2 - 2020-06-08

### Bugfix
- Fixed hardcoded cpTrigger in consent view pagination (#31)

## 1.3.1 - 2020-05-24

### Bugfix
- Updated schema version to automatically apply migration. (#25)

## 1.3.0 - 2020-05-23

> {warning} Please backup your database before updating. Also backup your database before attempting to use the console command for the fist time.

> {info} If you've created custom templates for the banner you might have to update them for this version to work.

### Added
- Added option to add a "Accept All" button (#19)
- Added console command for automatically remove old consents (#20)
- Added option to choose cookie name for the plugins consent (#21)
- Added crude pagination for consent overview

### Updated
- Changed default setting to have all optional groups toggled off (#19)

## 1.2.8 - 2020-02-11

### Bugfix
- Fixed a bug where EditableTables in settings did not output the existing rows. This was due to the values being delivered as a string, not as json. (#16)

## 1.2.7 - 2020-01-28

### Bugfix
- Fixed a bug with some elements being null when the cookie tab is not shown after consent

## 1.2.6 - 2020-01-28

### Bugfix
- Fixed a bug where the id field was populated with an empty string for new records. This was not accepted by postgres DBs (#13)

## 1.2.5 - 2020-01-16

### Bugfix
- Merged in PR #12 from @NRauh to solve issue with consent before the DOM is fully loaded.

### Enhancements
- Closing down the consent window before the ajax request to make the function feel more responsive and snappy

## 1.2.4 - 2019-10-25

### Fixed
- Merged in PR #9 from @jtrobinson1993 to follow basic accessibility guidelines.

## 1.2.3 - 2019-09-30

### Fixed
- Changed license to proprietary in composer.json for packagist 

## 1.2.2 - 2019-09-24

### Updated
- Consent storage moved from session to cookie
- Composer.json updated to reflect new version

## 1.2.1 - 2019-09-24

### Enhancements
- Consent storage moved from session to cookie

## 1.2.0 - 2019-08-02

### Bugfix
- Removed alert rendering from site settings (\#1)
- CP Links now utilizes cpUrl() to determine link href (\#2)
- Popup now utilizes actionUrl() to determine post target (\#2)
- CP Links now utilizes cpUrl() to determine link href (\#3)
- Cookie accept form now display your current selection after page refresh
- Consents are now ordered by date in the CP list


## 1.1.0 - 2019-05-14

### Updated
- Added margins to checkboxes

### Added
- Toggle checkboxes on/off for the initial small box
- Toggle a small cookie tab to bring back the consent form after consent is given

### Updated
- README now includes an example for SEOmatic

## 1.0.0.3 - 2019-05-14

### Bugfix
- The plugin now checks if it's a CP request when rendering the template

## 1.0.0.2 - 2019-05-14

### Updated
- License

## 1.0.0.1 - 2019-05-14

### Added
- License

## 1.0.0 - 2019-05-14

### Added
- Initial Plugin Release
