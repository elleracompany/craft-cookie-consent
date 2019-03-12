<?php
namespace elleracompany\cookieconsent;

use Craft;
use craft\web\View;

/**
 * Class Plugin
 *
 * @package elleracompany\cookieconsent
 */
class CookieConsent extends \craft\base\Plugin
{
	/**
	 * Enable settings in CP
	 * @var bool
	 */
	public $hasCpSettings = true;

	/**
	 * Plugin Initiator
	 */
	public function init()
	{
		parent::init();
		if(!isset($_COOKIE['cookieConsent'])) Craft::$app->view->hook('before-body-end', function(array &$context) {
			return $this->renderPluginTemplate('cookie-consent/banner', [
				'banner' => $this->getSettings()
			]);
		});
	}

	/**
	 * Returns the settings model
	 *
	 * @return \craft\base\Model|models\Settings|null
	 */
	protected function createSettingsModel()
	{
		return new \elleracompany\cookieconsent\models\Settings();
	}

	/**
	 * Returns the setting HTML for CP
	 *
	 * @return null|string
	 * @throws \Twig_Error_Loader
	 * @throws \yii\base\Exception
	 */
	protected function settingsHtml()
	{
		return \Craft::$app->getView()->renderTemplate('cookie-consent/settings', [
			'settings' => $this->getSettings()
		]);
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
}