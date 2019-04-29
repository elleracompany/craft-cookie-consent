<?php


namespace elleracompany\cookieconsent;

class JSAssets extends \craft\web\AssetBundle
{
	public function init()
	{
		$this->sourcePath = '@elleracompany/cookieconsent/resources';

		$this->depends = [

		];

		$this->js = [
			'script.js',
		];

		$this->css = [

		];

		parent::init();
	}
}