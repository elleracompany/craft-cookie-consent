<?php


namespace elleracompany\cookieconsent;

class CSSAssets extends \craft\web\AssetBundle
{
	public function init()
	{
		$this->sourcePath = '@elleracompany/cookieconsent/resources';

		$this->depends = [

		];

		$this->js = [

		];

		$this->css = [
			'style.css',
		];

		parent::init();
	}
}