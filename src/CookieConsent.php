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
	 * Plugin Initiator
	 */
	public function init()
	{
		parent::init();
		if(!isset($_COOKIE['cookieConsent'])) Craft::$app->view->hook('before-body-end', function(array &$context) {
			return $this->renderPluginTemplate('cookie-consent/consent');
		});
	}

	/**
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