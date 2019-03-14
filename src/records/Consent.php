<?php


namespace elleracompany\cookieconsent\records;

use craft\db\ActiveRecord;
use elleracompany\cookieconsent\CookieConsent;

/**
 * This is the model class for table "auth_item".
 *
 * @property boolean 	$activated
 * @property integer 	$site_id
 * @property string 	$headline
 * @property string 	$description
 * @property string		$template
 */
class Consent extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function fields()
	{
		$fields = [
			'site_id',
			'ip',
			'data',
		];
		return array_merge($fields, parent::fields());
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return CookieConsent::CONSENT_TABLE;
	}

	public function rules()
	{
		return [
			[['data'], 'string'],
			[['site_id', 'description', 'template'], 'required'],
			['site_id', 'integer']
		];
	}
}
