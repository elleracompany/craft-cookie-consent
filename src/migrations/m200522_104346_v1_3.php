<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m200522_104346_v1_3
 */
class m200522_104346_v1_3 extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->addColumn(
			CookieConsent::SITE_SETTINGS_TABLE,
			'cookieName',
			$this->string()->notNull()->defaultValue('cookie-consent')
		);
		$this->addColumn(
			CookieConsent::SITE_SETTINGS_TABLE,
			'acceptAllButton',
			$this->boolean()->notNull()->defaultValue(false)
		);
        $this->addColumn(
            CookieConsent::CONSENT_TABLE,
            'cookieName',
            $this->string()->defaultValue('cookie-consent')
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