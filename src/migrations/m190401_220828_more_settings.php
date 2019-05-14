<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m190414_150622_more_settings
 */
class m190401_220828_more_settings extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->addColumn(
			CookieConsent::SITE_SETTINGS_TABLE,
			'showCheckboxes',
			$this->boolean()->notNull()->defaultValue(true)
		);
		$this->addColumn(
			CookieConsent::SITE_SETTINGS_TABLE,
			'showAfterConsent',
			$this->boolean()->notNull()->defaultValue(true)
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