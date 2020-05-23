<?php


namespace elleracompany\cookieconsent\records;

use craft\db\ActiveRecord;
use elleracompany\cookieconsent\CookieConsent;

/**
 * This is the model class for table "auth_item".
 *
 * @property integer 	$site_id
 * @property string 	$ip
 * @property string     $cookieName
 * @property string 	$data
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
            'cookieName',
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
			[['data', 'cookieName'], 'string'],
			[['site_id', 'data'], 'required'],
			['site_id', 'integer']
		];
	}
}
