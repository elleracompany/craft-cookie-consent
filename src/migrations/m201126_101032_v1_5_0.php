<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m201126_101032_v1_5_0
 */
class m200619_074354_v1_4_2 extends Migration
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