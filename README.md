# Craft Cookie Consent

Elleras Cookie Consent Plugin

## Create hook in your template
Add this line before your end-body-tag:

`{% hook 'before-body-end' %}`
## Check for user consent
`{% if craft.cookieConsent.getConsent('slug') %}`