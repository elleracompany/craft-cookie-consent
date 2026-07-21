<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * Adds an index for the queries used by the consent list in the CP
 * and the retention console command, which both filter on site_id
 * and dateUpdated.
 */
class m260721_000000_add_consent_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndex(
            'idx_cookie_consent_consent_site_date',
            CookieConsent::CONSENT_TABLE,
            ['site_id', 'dateUpdated']
        );
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropIndex('idx_cookie_consent_consent_site_date', CookieConsent::CONSENT_TABLE);
        return true;
    }
}
