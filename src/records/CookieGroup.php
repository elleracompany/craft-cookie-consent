<?php


namespace elleracompany\cookieconsent\records;

use craft\db\ActiveRecord;
use elleracompany\cookieconsent\CookieConsent;
use yii\behaviors\SluggableBehavior;
/**
 * This is the model class for table "auth_item".
 *
 * @property integer 	$id
 * @property boolean 	$required
 * @property boolean 	$store_ip
 * @property boolean 	$default
 * @property integer 	$site_id
 * @property string 	$name
 * @property string 	$description
 * @property string		$slug
 */
class CookieGroup extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		$behaviors = [
			[
				'class' => SluggableBehavior::className(),
				'attribute' => 'name',
				'slugAttribute' => 'slug',
				'ensureUnique' => true,
				'immutable' => true,
				'uniqueSlugGenerator' => function ($baseSlug, $iteration, $model)
				{
					return $iteration > 1 ? $baseSlug.'-'.$model->site_id.'-'.$iteration : $baseSlug.'-'.$model->site_id;
				}
			],
		];
		return array_merge($behaviors, parent::behaviors());
	}

	/**
	 * @inheritdoc
	 */
	public function fields()
	{
		$fields = [
			'id',
			'name',
			'slug',
			'required',
			'store_ip',
			'description',
			'site_id'
		];
		return array_merge($fields, parent::fields());
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return CookieConsent::COOKIE_GROUP_TABLE;
	}

	public function rules()
	{
		return [
			[['name', 'slug', 'description'], 'string'],
			[['site_id', 'description', 'name'], 'required'],
			[['required', 'store_ip', 'default'], 'boolean'],
			[['required', 'store_ip', 'default'], 'default', 'value' => 0],
			['site_id', 'integer']
		];
	}
}
