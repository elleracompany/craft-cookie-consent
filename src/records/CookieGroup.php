<?php


namespace elleracompany\cookieconsent\records;

use Craft;
use craft\db\ActiveRecord;
use craft\records\Site;
use elleracompany\cookieconsent\CookieConsent;
use yii\behaviors\SluggableBehavior;
use yii\web\NotFoundHttpException;

/**
 * Cookie group record, one row per consent category on a site.
 *
 * @property integer $id
 * @property boolean $required
 * @property boolean $store_ip
 * @property boolean $default
 * @property integer $site_id
 * @property string|array $cookies
 * @property string|array $consent_signals
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property integer $order
 * @property-read Site $site
 * @property-read SiteSettings $siteSettings
 */
class CookieGroup extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = [
            [
                'class' => SluggableBehavior::class,
                'attribute' => 'slugattribute',
                'slugAttribute' => 'slug',
                'ensureUnique' => true,
                'immutable' => false,
                'uniqueSlugGenerator' => function ($baseSlug, $iteration, $model)
                {
                    return $baseSlug.'-'.$iteration;
                }
            ],
        ];
        return array_merge($behaviors, parent::behaviors());
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = [
            'name',
            'slug',
            'required',
            'cookies',
            'consent_signals',
            'store_ip',
            'description',
            'site_id',
            'order'
        ];
        return array_merge($fields, parent::fields());
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Craft::t('cookie-consent', 'ID'),
            'name' => Craft::t('cookie-consent', 'Name'),
            'slug' => Craft::t('cookie-consent', 'Slug'),
            'required' => Craft::t('cookie-consent', 'Required'),
            'default' => Craft::t('cookie-consent', 'Default'),
            'cookies' => Craft::t('cookie-consent', 'Cookies'),
            'consent_signals' => Craft::t('cookie-consent', 'Google Consent Mode signals'),
            'store_ip' => Craft::t('cookie-consent', 'Store IP'),
            'description' => Craft::t('cookie-consent', 'Description'),
            'site_id' => Craft::t('cookie-consent', 'Site ID'),
            'order' => Craft::t('cookie-consent', 'Order')
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return CookieConsent::COOKIE_GROUP_TABLE;
    }

    public function rules(): array
    {
        return [
            [['name', 'slug', 'description', 'cookies'], 'string'],
            ['consent_signals', 'validateConsentSignals'],
            [['site_id', 'description', 'name', 'slug'], 'required'],
            [['required', 'store_ip', 'default'], 'boolean'],
            [['required', 'store_ip', 'default', 'order'], 'default', 'value' => 0],
            [['order'], 'integer', 'min' => 0, 'max' => 100],
            ['site_id', 'integer']
        ];
    }

    public function getSlugattribute(): string
    {
        return $this->site->handle.'-'.$this->name;
    }

    public function beforeSave($insert): bool
    {
        $this->stringifyCookies();
        $this->stringifyConsentSignals();
        return parent::beforeSave($insert);
    }

    /**
     * Validates that consent_signals only contains signals defined by
     * Google Consent Mode v2
     *
     * @param $attribute
     * @param $params
     */
    public function validateConsentSignals($attribute, $params): void
    {
        $signals = $this->$attribute;
        if (is_string($signals)) {
            $signals = json_decode($signals, true);
        }
        if ($signals === null || $signals === '') {
            return;
        }
        if (!is_array($signals)) {
            $this->addError($attribute, Craft::t('cookie-consent', 'Consent signals must be a list.'));
            return;
        }
        foreach ($signals as $signal) {
            if (!in_array($signal, CookieConsent::GCM_SIGNALS, true)) {
                $this->addError($attribute, Craft::t('cookie-consent', 'Unknown consent signal: {signal}', ['signal' => $signal]));
            }
        }
    }

    /**
     * The Google Consent Mode signals granted by this group, as an array
     *
     * @return string[]
     */
    public function getSignals(): array
    {
        $signals = $this->consent_signals;
        if (is_string($signals)) {
            $signals = json_decode($signals, true);
        }
        return is_array($signals) ? $signals : [];
    }

    /**
     * Returns edit url for the current record
     *
     * @return string
     * @throws \craft\errors\MissingComponentException
     */
    public function getEditUrl(): string
    {
        if ($this->site_id && Craft::$app->sites->getSiteById($this->site_id)) {
            return 'cookie-consent/group/'.$this->id;
        }
        Craft::$app->getSession()->setError(Craft::t('cookie-consent', 'Could not get return url.'));
        return 'cookie-consent';
    }

    public function afterSave($insert, $changedAttributes): void
    {
        $this->unstringifyCookies();
        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeValidate(): bool
    {
        $this->stringifyCookies();
        $this->stringifyConsentSignals();
        return parent::beforeValidate();
    }

    private function stringifyCookies(): void
    {
        if (is_array($this->cookies)) {
            $this->cookies = json_encode($this->cookies);
        }
    }

    private function stringifyConsentSignals(): void
    {
        if (is_array($this->consent_signals)) {
            $this->consent_signals = json_encode(array_values($this->consent_signals));
        }
    }

    public function unstringifyCookies(): void
    {
        if (!is_array($this->cookies)) {
            $this->cookies = json_decode($this->cookies, true);
        }
    }

    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'site_id']);
    }

    public function getSiteSettings()
    {
        return $this->hasOne(SiteSettings::class, ['site_id' => 'site_id']);
    }
}
