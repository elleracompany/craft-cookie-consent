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
 * @property boolean $activated
 * @property boolean $jsAssets
 * @property boolean $cssAssets
 * @property boolean $templateAsset
 * @property boolean $showCheckboxes
 * @property boolean $showAfterConsent
 * @property boolean    $acceptAllButton
 * @property boolean    $enableConsentMode
 * @property string     $gcmDataLayerName
 * @property integer    $gcmWaitForUpdate
 * @property boolean    $gcmAdsDataRedaction
 * @property boolean    $gcmUrlPassthrough
 * @property boolean    $refresh
 * @property string     $cookieName
 * @property integer    $cookieExpiry
 * @property string $headline
 * @property string $description
 * @property string $template
 * @property integer $site_id
 * @property integer    $refresh_time
 * @property string     $dateInvalidated
 * @property-read CookieGroup[] $cookieGroups
 * @property-read Site $site
 */
class SiteSettings extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = [
            'site_id',
            'activated',
            'cssAssets',
            'jsAssets',
            'showCheckboxes',
            'showAfterConsent',
            'cookieName',
            'cookieExpiry',
            'headline',
            'description',
            'template',
            'templateAsset',
            'acceptAllButton',
            'enableConsentMode',
            'gcmDataLayerName',
            'gcmWaitForUpdate',
            'gcmAdsDataRedaction',
            'gcmUrlPassthrough',
            'refresh',
            'refresh_time',
            'dateInvalidated'
        ];
        return array_merge($fields, parent::fields());
    }

    public function behaviors(): array
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
    public function rules(): array
    {
        return [
            [['headline', 'description', 'template', 'cookieName', 'gcmDataLayerName', 'dateInvalidated'], 'string'],
            [['headline', 'description', 'template'], 'required'],
            [['refresh_time'], 'default', 'value'=> 500],
            [['cookieExpiry'], 'default', 'value' => 365],
            [['cookieExpiry'], 'integer', 'min' => 1],
            [['gcmDataLayerName'], 'default', 'value' => 'dataLayer'],
            [['gcmDataLayerName'], 'match', 'pattern' => '/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', 'message' => Craft::t('cookie-consent', 'The data layer name must be a valid JavaScript variable name.')],
            [['gcmWaitForUpdate'], 'default', 'value' => 2000],
            [['gcmWaitForUpdate'], 'integer', 'min' => 0],
            [['activated', 'cssAssets', 'jsAssets', 'templateAsset', 'showCheckboxes', 'showAfterConsent', 'acceptAllButton', 'enableConsentMode', 'gcmAdsDataRedaction', 'gcmUrlPassthrough', 'refresh'], 'boolean'],
            [['activated', 'headline', 'description', 'template', 'templateAsset', 'showCheckboxes', 'showAfterConsent'], 'validatePermission'],
            [['activated', 'acceptAllButton', 'enableConsentMode', 'gcmAdsDataRedaction', 'gcmUrlPassthrough', 'refresh'], 'default', 'value' => 0],
            [['cssAssets', 'jsAssets', 'templateAsset'], 'default', 'value' => 1],
            [['site_id', 'refresh_time', 'cookieExpiry'], 'integer']
        ];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels(): array
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
            'cookieExpiry' => Craft::t('cookie-consent', 'Consent cookie expiry (days)'),
            'acceptAllButton' => Craft::t('cookie-consent', 'Add "Accept All" button'),
            'enableConsentMode' => Craft::t('cookie-consent', 'Enable Google Consent Mode v2'),
            'gcmDataLayerName' => Craft::t('cookie-consent', 'Data layer name'),
            'gcmWaitForUpdate' => Craft::t('cookie-consent', 'Wait for update (ms)'),
            'gcmAdsDataRedaction' => Craft::t('cookie-consent', 'Redact ads data'),
            'gcmUrlPassthrough' => Craft::t('cookie-consent', 'URL passthrough'),
            'description' => Craft::t('cookie-consent', 'Description'),
            'template' => Craft::t('cookie-consent', 'Template'),
            'refresh' => Craft::t('cookie-consent', 'Automatic Refresh'),
            'refresh_time' => Craft::t('cookie-consent', 'Refresh Time')
        ];
    }

    /**
     * Map of attribute name to the permission required to change it
     *
     * @return array<string,string>
     */
    public function permissions(): array
    {
        return [
            'activated' => 'cookie-consent:site-settings:activate',
            'headline' => 'cookie-consent:site-settings:content',
            'description' => 'cookie-consent:site-settings:content',
            'showCheckboxes' => 'cookie-consent:site-settings:content',
            'showAfterConsent' => 'cookie-consent:site-settings:content',
            'cookieName' => 'cookie-consent:site-settings:content',
            'cookieExpiry' => 'cookie-consent:site-settings:content',
            'acceptAllButton' => 'cookie-consent:site-settings:content',
            'enableConsentMode' => 'cookie-consent:site-settings:content',
            'gcmDataLayerName' => 'cookie-consent:site-settings:content',
            'gcmWaitForUpdate' => 'cookie-consent:site-settings:content',
            'gcmAdsDataRedaction' => 'cookie-consent:site-settings:content',
            'gcmUrlPassthrough' => 'cookie-consent:site-settings:content',
            'template' => 'cookie-consent:site-settings:template',
            'templateAsset' => 'cookie-consent:site-settings:template'
        ];
    }

    /**
     * @param $attribute
     * @param $params
     */
    public function validatePermission($attribute, $params): void
    {
        $attribute_to_permission = $this->permissions();
        if (array_key_exists($attribute, $this->getDirtyAttributes()) && !Craft::$app->user->checkPermission($attribute_to_permission[$attribute])) {
            $this->addError($attribute, Craft::t('cookie-consent', 'You do not have permission to change this attribute'));
        }
    }

    /**
     * Timestamp of the most recent update to the settings or any cookie group
     *
     * @return int|null
     */
    public function getLastUpdate(): ?int
    {
        $dates = [];
        if (isset($this->dateUpdated)) {
            $dates[] = strtotime($this->dateUpdated);
        }
        foreach ($this->cookieGroups as $group) {
            if (isset($group->dateUpdated)) {
                $dates[] = strtotime($group->dateUpdated);
            }
        }
        return $dates ? max($dates) : null;
    }

    /**
     * Returns the URL to edit this record
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function getEditUrl(): string
    {
        if ($this->site_id) {
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
        foreach ($this->cookieGroups as $group) {
            if (isset($group->required) && $group->required) {
                $required[] = $group->slug;
            }
        }
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
     * @param int|null $siteId
     *
     * @return string
     * @throws NotFoundHttpException
     */
    protected function getSiteHandleFromId(?int $siteId): string
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
