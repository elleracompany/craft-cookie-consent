<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * Adds Google Consent Mode v2 support (#106): cookie groups can be mapped
 * to Google consent signals, and site settings gain a toggle plus the
 * gtag configuration options.
 */
class m260721_140000_add_consent_mode extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(
            CookieConsent::COOKIE_GROUP_TABLE,
            'consent_signals',
            $this->text()->after('cookies')
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'enableConsentMode',
            $this->boolean()->notNull()->defaultValue(false)->after('acceptAllButton')
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'gcmDataLayerName',
            $this->string()->notNull()->defaultValue('dataLayer')->after('enableConsentMode')
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'gcmWaitForUpdate',
            $this->integer()->notNull()->defaultValue(2000)->after('gcmDataLayerName')
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'gcmAdsDataRedaction',
            $this->boolean()->notNull()->defaultValue(false)->after('gcmWaitForUpdate')
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'gcmUrlPassthrough',
            $this->boolean()->notNull()->defaultValue(false)->after('gcmAdsDataRedaction')
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(CookieConsent::COOKIE_GROUP_TABLE, 'consent_signals');
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'enableConsentMode');
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'gcmDataLayerName');
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'gcmWaitForUpdate');
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'gcmAdsDataRedaction');
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'gcmUrlPassthrough');
        return true;
    }
}
