<?php


namespace elleracompany\cookieconsent;

class AssetBundle extends \craft\web\AssetBundle
{
	public function init()
	{
		// define the path that your publishable resources live
		$this->sourcePath = '@elleracompany/cookieconsent/resources';

		// define the dependencies
		$this->depends = [

		];

		// define the relative path to CSS/JS files that should be registered with the page
		// when this asset bundle is registered
		$this->js = [
			'script.js',
		];

		$this->css = [
			'style.css',
		];

		parent::init();
	}
}