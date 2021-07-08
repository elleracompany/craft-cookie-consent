<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m201126_101032_v1_5_0
 */
class m201126_101032_v1_5_0 extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
	    $this->alterColumn(
            CookieConsent::CONSENT_TABLE,
            'ip',
            $this->string(39)->defaultValue(null)
        );
	    $this->addColumn(
	        CookieConsent::SITE_SETTINGS_TABLE,
            'dateInvalidated',
            $this->dateTime()->notNull()->defaultValue('2019-05-14 00:00:00')
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