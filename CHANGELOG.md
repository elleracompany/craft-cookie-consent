# Release Notes for Cookie Consent

## 1.6.0 - 2021-07-08

### Added
- Added ability to modify slug of cookie groups (#68)

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