<?php


namespace elleracompany\cookieconsent\records;

use Craft;
use craft\db\ActiveRecord;
use craft\helpers\Db;
use craft\records\Site;
use elleracompany\cookieconsent\CookieConsent;
use yii\db\ActiveQueryInterface;
use yii\web\NotFoundHttpException;
use yii\behaviors\TimestampBehavior;

/**
 * @property boolean 	$activated
 * @property boolean 	$jsAssets
 * @property boolean 	$cssAssets
 * @property boolean	$templateAsset
 * @property boolean	$showCheckboxes
 * @property boolean	$showAfterConsent
 * @property boolean    $acceptAllButton
 * @property boolean    $refresh
 * @property string     $cookieName
 * @property string 	$headline
 * @property string 	$description
 * @property string		$template
 * @property integer 	$site_id
 * @property integer    $refresh_time
 * @property string     $dateInvalidated
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
			'cssAssets',
			'jsAssets',
			'showCheckboxes',
			'showAfterConsent',
            'cookieName',
			'headline',
			'description',
			'template',
			'templateAsset',
            'acceptAllButton',
            'refresh',
            'refresh_time',
            'dateInvalidated'
		];
		return array_merge($fields, parent::fields());
	}

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'dateInvalidated',
                'updatedAtAttribute' => null,
                'value' => Db::prepareDateForDb(new \DateTime())
            ],
        ];
    }

	/**
	 * @inheritdoc
	 */
	public static function tableName(): string
	{
		return CookieConsent::SITE_SETTINGS_TABLE;
	}

    /**
     * @inheritDoc
     */
	public function rules()
	{
		return [
			[['headline', 'description', 'template', 'cookieName', 'dateInvalidated'], 'string'],
			[['headline', 'description', 'template'], 'required'],
            [['refresh_time'], 'default', 'value'=> 500],
			[['activated', 'cssAssets', 'jsAssets', 'templateAsset', 'showCheckboxes', 'showAfterConsent', 'acceptAllButton', 'refresh'], 'boolean'],
			[['activated', 'headline', 'description', 'template', 'templateAsset', 'showCheckboxes', 'showAfterConsent'], 'validatePermission'],
			[['activated', 'acceptAllButton', 'refresh'], 'default', 'value' => 0],
			[['cssAssets', 'jsAssets', 'templateAsset'], 'default', 'value' => 1],
			[['site_id', 'refresh_time'], 'integer']
		];
	}

    /**
     * @inheritDoc
     */
	public function attributeLabels()
	{
		return [
			'site_id' => Craft::t('cookie-consent', 'Site ID'),
			'activated' => Craft::t('cookie-consent', 'Activated'),
			'cssAssets' => Craft::t('cookie-consent', 'Load CSS Assets'),
			'jsAssets' => Craft::t('cookie-consent', 'Load JS Assets'),
			'templateAsset' => Craft::t('cookie-consent', 'Load Template'),
			'showCheckboxes' => Craft::t('cookie-consent', 'Show Checkboxes'),
			'showAfterConsent' => Craft::t('cookie-consent', 'Show after Consent'),
			'headline' => Craft::t('cookie-consent', 'Headline'),
            'cookieName' => Craft::t('cookie-consent', 'Name of the consent cookie'),
            'acceptAllButton' => Craft::t('cookie-consent', 'Add "Accept All" button'),
			'description' => Craft::t('cookie-consent', 'Description'),
			'template' => Craft::t('cookie-consent', 'Template'),
            'refresh' => Craft::t('cookie-consent', 'Automatic Refresh'),
            'refresh_time' => Craft::t('cookie-consent', 'Refresh Time')
		];
	}

    /**
     * @return array
     */
	public function permissions()
	{
		return [
			'activated' => 'cookie-consent:site-settings:activate',
			'headline' => 'cookie-consent:site-settings:content',
			'description' => 'cookie-consent:site-settings:content',
			'showCheckboxes' => 'cookie-consent:site-settings:content',
			'showAfterConsent' => 'cookie-consent:site-settings:content',
            'cookieName' => 'cookie-consent:site-settings:content',
            'acceptAllButton' => 'cookie-consent:site-settings:content',
			'template' => 'cookie-consent:site-settings:template',
			'templateAsset' => 'cookie-consent:site-settings:template'
		];
	}

    /**
     * @param $attribute
     * @param $params
     */
	public function validatePermission($attribute, $params)
	{
		$attribute_to_permission = $this->permissions();
		if(in_array($attribute, array_keys($this->getDirtyAttributes())) && !Craft::$app->user->checkPermission($attribute_to_permission[$attribute])) {
			$this->addError($attribute, Craft::t('cookie-consent', 'You do not have permission to change this attribute'));
		}
	}

    /**
     * @return mixed
     */
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
		return $this->hasMany(CookieGroup::class, ['site_id' => 'site_id'])->orderBy('order, id');
	}

    /**
     * @return array
     */
	public function getRequiredCookieGroups() : array
	{
		$required = [];
		foreach ($this->cookieGroups as $group) if(isset($group->required) && $group->required) $required[] = $group->slug;
		return $required;
	}

    /**
     * @return ActiveQueryInterface
     */
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
