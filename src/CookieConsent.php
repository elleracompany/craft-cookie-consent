<?php
namespace elleracompany\cookieconsent;

use Craft;
use craft\web\View;
use elleracompany\cookieconsent\services\Variables;
use yii\base\Event;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;

/**
 * Class Plugin
 *
 * @package elleracompany\cookieconsent
 */
class CookieConsent extends \craft\base\Plugin
{
	// Constants
	// =========================================================================

	/**
	 * Database Table name for SiteSettings records
	 */
	const SITE_SETTINGS_TABLE = '{{%cookie_consent_site_settings}}';

	/**
	 * Database Table name for Cookie Group records
	 */
	const COOKIE_GROUP_TABLE = '{{%cookie_consent_group}}';

	/**
	 * Database Table name for Cookie Consent records
	 */
	const CONSENT_TABLE = '{{%cookie_consent_consent}}';

	/**
	 * Default banner template location
	 */
	const DEFAULT_TEMPLATE = 'cookie-consent/banner';

	/**
	 * Default banner headline
	 */
	const DEFAULT_HEADLINE = 'This website uses cookies';

	/**
	 * Default banner description
	 */
	const DEFAULT_DESCRIPTION = 'We use cookies to personalise content and ads, and to analyse our traffic and improve our service. You consent to cookies if you continue to use our website.';

	/**
	 * Default cookie groups
	 */
	const DEFAULT_GROUPS = [
		[
			'required' => true,
			'store_ip' => false,
			'default' => true,
			'name' => 'Necessary',
			'description' => 'Cookies that the site cannot function properly without. This includes cookies for access to secure areas and CSRF security. Please note that Craft’s default cookies do not collect any personal or sensitive information. Craft\'s default cookies do not collect IP addresses. The information they store is not sent to Pixel & Tonic or any 3rd parties.',
			'cookies' => [
				[
					'name' => 'CraftSessionId',
					'description' => 'Craft relies on PHP sessions to maintain sessions across web requests. That is done via the PHP session cookie. Craft names that cookie “CraftSessionId” by default, but it can be renamed via the phpSessionId config setting. This cookie will expire as soon as the session expires.',
					'provider' => 'this site',
					'expiry' => 'Session',
				],
				[
					'name' => '*_identity',
					'description' => 'When you log into the Control Panel, you will get an authentication cookie used to maintain your authenticated state. The cookie name is prefixed with a long, randomly generated string, followed by _identity. The cookie only stores information necessary to maintain a secure, authenticated session and will only exist for as long as the user is authenticated in Craft.',
					'provider' => 'this site',
					'expiry' => 'Persistent',
				],
				[
					'name' => '*_username',
					'description' => 'If you check the "Keep me logged in" option during login, this cookie is used to remember the username for your next authentication.',
					'provider' => 'this site',
					'expiry' => 'Persistent',
				],
				[
					'name' => 'CRAFT_CSRF_TOKEN',
					'description' => 'Protects us and you as a user against Cross-Site Request Forgery attacks.',
					'provider' => 'this site',
					'expiry' => 'Session',
				]
			]
		],
		[
			'required' => false,
			'store_ip' => false,
			'default' => true,
			'name' => 'Statistics',
			'description' => 'Statistic cookies help us understand how visitors interact with websites by collecting and reporting information anonymously.',
			'cookies' => []
		],
		[
			'required' => false,
			'store_ip' => true,
			'default' => false,
			'name' => 'Marketing',
			'description' => 'Marketing cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user and thereby more valuable for publishers and third party advertisers.',
			'cookies' => []
		]
	];

	/**
	 * Plugin name
	 */
	const PLUGIN_NAME = 'Cookie Banner';

	// Properties
	// =========================================================================

	/**
	 * Enable CpNav
	 *
	 * @var bool
	 */
	public $hasCpSection = true;

	/**
	 * Schema version
	 * For applying migrations etc.
	 *
	 * @var string
	 */
	public $schemaVersion = '0.0.1';

	// Public Methods
	// =========================================================================

	/**
	 * Plugin Initiator
	 */
	public function init()
	{
		parent::init();
		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
			$e->sender->set('cookieConsent', Variables::class);
		});
		$this->setComponents([
			'cookieConsent' => Variables::class,
		]);
		if(!Craft::$app->request->isCpRequest) {
			if($this->cookieConsent->render()) Craft::$app->view->hook('before-body-end', function(array &$context) {

				return $this->renderPluginTemplate('cookie-consent/banner', [
					'banner' => $this->getSettings()
				]);
			});
		}
		else $this->installCpEventListeners();
	}

	/**
	 * @inheritdoc
	 */
	public function getSettingsResponse()
	{
		// Just redirect to the plugin settings page
		Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('cookie-consent'));
	}

	/**
	 * Shuffling template mode and rendering template
	 * and JS/CSS files.
	 *
	 * https://docs.craftcms.com/v3/extend/updating-plugins.html#rendering-templates
	 *
	 * @param       $path
	 * @param array $params
	 *
	 * @return string
	 * @throws \Twig_Error_Loader
	 * @throws \yii\base\Exception
	 */
	public function renderPluginTemplate($path, $params = [])
	{
		$oldMode = Craft::$app->view->getTemplateMode();
		Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
		$html = Craft::$app->view->renderTemplate($path,$params);
		Craft::$app->view->setTemplateMode($oldMode);
		return $html;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Install CP Event Listeners
	 */
	protected function installCpEventListeners()
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				Craft::debug('Loaded CookieConsent CP Routes', 'cookie-consent');
				$event->rules = array_merge(
					$event->rules,
					$this->customAdminCpRoutes()
				);
			}
		);
	}

	/**
	 * Return the custom Control Panel routes
	 *
	 * @return array
	 */
	protected function customAdminCpRoutes(): array
	{
		return [
			'cookie-consent' 														=>	'cookie-consent/settings/edit-site-settings',
			'cookie-consent/site/<siteHandle:{handle}>'								=> 	'cookie-consent/settings/edit-site-settings',
			'cookie-consent/group/<siteHandle:{handle}>'							=> 	'cookie-consent/settings/edit-cookie-group',
			'cookie-consent/group/<siteHandle:{handle}>/<groupId:\d+>'			=> 	'cookie-consent/settings/edit-cookie-group',
		];
	}
}