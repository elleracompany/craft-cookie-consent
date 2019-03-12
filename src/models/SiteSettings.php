<?php


namespace elleracompany\cookieconsent\models;

use craft\base\Model;

class SiteSettings extends Model
{
	public $headline = 'Privacy';
	public $description = 'A short disclaimer for the quick accept button.';

	public function rules()
	{
		return [
			[['headline', 'description'], 'string']
		];
	}
}