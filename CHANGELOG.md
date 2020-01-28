# Release Notes for Cookie Consent

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