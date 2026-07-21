<?php


namespace elleracompany\cookieconsent\records;

use craft\db\ActiveRecord;
use elleracompany\cookieconsent\CookieConsent;

/**
 * A stored consent submission.
 *
 * @property string $uid
 * @property integer $site_id
 * @property string $ip
 * @property string     $cookieName
 * @property string $data
 */
class Consent extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function fields(): array
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

    public function rules(): array
    {
        return [
            [['data', 'cookieName'], 'string'],
            [['site_id', 'data'], 'required'],
            ['site_id', 'integer']
        ];
    }
}
