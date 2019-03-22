<?php


namespace elleracompany\cookieconsent\records;

use Craft;
use craft\db\ActiveRecord;
use craft\records\Site;
use elleracompany\cookieconsent\CookieConsent;
use yii\db\ActiveQueryInterface;
use yii\web\NotFoundHttpException;

/**
 * This is the model class for table "auth_item".
 *
 * @property boolean 	$activated
 * @property integer 	$site_id
 * @property string 	$headline
 * @property string 	$description
 * @property string		$template
 */
class SiteSettings extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function fields()
	{
		$fields = [
			'site_id',
			'activated',
			'headline',
			'description',
			'template'
		];
		return array_merge($fields, parent::fields());
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return CookieConsent::SITE_SETTINGS_TABLE;
	}

	public function rules()
	{
		return [
			[['headline', 'description', 'template'], 'string'],
			[['headline', 'description', 'template'], 'required'],
			['activated', 'boolean'],
			[['activated', 'headline', 'description', 'template'], 'validatePermission'],
			['activated', 'default', 'value' => 0],
			['site_id', 'integer']
		];
	}

	public function attributeLabels()
	{
		return [
			'site_id' => Craft::t('cookie-consent', 'Site ID'),
			'activated' => Craft::t('cookie-consent', 'Activated'),
			'headline' => Craft::t('cookie-consent', 'Headline'),
			'description' => Craft::t('cookie-consent', 'Description'),
			'template' => Craft::t('cookie-consent', 'Template')
		];
	}

	public function permissions()
	{
		return [
			'activated' => 'cookie-consent:site-settings:activate',
			'headline' => 'cookie-consent:site-settings:content',
			'description' => 'cookie-consent:site-settings:content',
			'template' => 'cookie-consent:site-settings:template'
		];
	}
	public function validatePermission($attribute, $params)
	{
		$attribute_to_permission = $this->permissions();
		if(in_array($attribute, array_keys($this->getDirtyAttributes())) && !Craft::$app->user->checkPermission($attribute_to_permission[$attribute])) {
			$this->addError($attribute, Craft::t('cookie-consent', 'You do not have permission to change this attribute'));
		}
	}

	public function getLastUpdate()
	{
		$dates = [];
		if(isset($this->dateUpdated)) $dates[] = strtotime($this->dateUpdated);
		foreach ($this->getCookieGroups() as $group) if(isset($group->dateUpdated)) $dates[] = strtotime($group->dateUpdated);
		return max($dates);
	}

	/**
	 * Returns the URL to edit this record
	 *
	 * @return int|null|string
	 * @throws NotFoundHttpException
	 */
	public function getEditUrl()
	{
		if($this->site_id) {
			return 'cookie-consent/site/'.$this->getSiteHandleFromId($this->site_id);
		}
		return 'cookie-consent';
	}

	/**
	 * Returns the site’s cookie groups.
	 *
	 * @return ActiveQueryInterface The relational query object.
	 */
	public function getCookieGroups(): ActiveQueryInterface
	{
		return $this->hasMany(CookieGroup::class, ['site_id' => 'site_id']);
	}

	public function getRequiredCookieGroups() : array
	{
		$required = [];
		foreach ($this->cookieGroups as $group) if(isset($group->required) && $group->required) $required[] = $group->slug;
	}

	public function getSite(): ActiveQueryInterface
	{
		return $this->hasOne(Site::class, ['id' => 'site_id']);
	}

	/**
	 * Return a siteHandle from a siteId
	 *
	 * @param string $siteId
	 *
	 * @return int|null
	 * @throws NotFoundHttpException
	 */
	protected function getSiteHandleFromId($siteId)
	{
		if ($siteId !== null) {
			$site = Craft::$app->getSites()->getSiteById($siteId);
			if (!$site) {
				throw new NotFoundHttpException('Invalid site ID: '.$siteId);
			}
			$siteHandle = $site->handle;
		} else {
			$siteHandle = Craft::$app->getSites()->currentSite->handle;
		}

		return $siteHandle;
	}
}
