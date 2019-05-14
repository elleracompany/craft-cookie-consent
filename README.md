# Craft Cookie Consent

Ellera Cookie Consent Plugin

## Install the plugin
You can install this plugin from the Plugin Store.

## Create hook in your template
To give you full control over where the plugin is rendering the consent template, you neede to add this line before your end-body-tag in the relevant layout file(s):

`{% hook 'before-body-end' %}`
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

## Acknowledgements
Plugin Icon designed by Trinh Ho from Flaticon