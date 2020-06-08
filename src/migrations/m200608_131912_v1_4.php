<?php

namespace elleracompany\cookieconsent\migrations;

use craft\db\Migration;
use elleracompany\cookieconsent\CookieConsent;

/**
 * m200608_131912_v1_4
 */
class m200608_131912_v1_4 extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->addColumn(
			CookieConsent::COOKIE_GROUP_TABLE,
			'order',
			$this->integer()
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