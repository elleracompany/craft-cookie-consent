<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * Adds a per-site expiry (in days) for the consent cookie, which was
 * previously hardcoded to one year (#121).
 */
class m260721_120000_add_cookie_expiry extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'cookieExpiry',
            $this->integer()->notNull()->defaultValue(365)->after('cookieName')
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(CookieConsent::SITE_SETTINGS_TABLE, 'cookieExpiry');
        return true;
    }
}
