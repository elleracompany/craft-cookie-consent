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
	/**
	 * Database Table name for SiteSettings record
	 */
	const SITE_SETTINGS_TABLE = '{{%cookie_consent_site_settings}}';
	const COOKIE_GROUP_TABLE = '{{%cookie_consent_group}}';
	const COOKIES_TABLE = '{{%cookie_consent_cookie}}';
	const CONSENT_TABLE = '{{%cookie_consent_consent}}';

	const PLUGIN_NAME = 'Cookie Banner';
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

	/**
	 *
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
			'cookie-consent' 														=>	'cookie-consent/settings/index',
			'cookie-consent/site/<siteHandle:{handle}>'								=> 	'cookie-consent/settings/index',
			'cookie-consent/group/<siteHandle:{handle}>'							=> 	'cookie-consent/settings/group',
			'cookie-consent/group/<siteHandle:{handle}>/<sectionId:\d+>'			=> 	'cookie-consent/settings/group',
		];
	}
}