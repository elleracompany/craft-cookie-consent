<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m200619_074354_v1_4_2
 */
class m200619_074354_v1_4_2 extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'refresh',
            $this->integer()->notNull()->defaultValue(false)
        );
        $this->addColumn(
            CookieConsent::SITE_SETTINGS_TABLE,
            'refresh_time',
            $this->integer()->notNull()->defaultValue(500)
        );
	}
	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "This migration cannot be reverted.";
		return false;
	}
}