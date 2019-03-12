<?php

namespace elleracompany\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
Use craft\db\Table;
use elleracompany\cookieconsent\CookieConsent;

/**
 * Install migration.
 */
class Install extends Migration
{
	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		$this->createTable(
			CookieConsent::SITE_SETTINGS_TABLE,
			[
				'site_id' => $this->integer(11),
				'activated' => $this->boolean()->notNull()->defaultValue(false),
				'headline' => $this->string(255)->notNull()->defaultValue(Craft::t('cookie-consent','Privacy')),
				'description' => $this->text()
			]
		);
		$this->addPrimaryKey(
			'pk_cookie_consent_site_settings',
			CookieConsent::SITE_SETTINGS_TABLE,
			'site_id'
		);
		$this->addForeignKey(
			'cookie_consent_setting_belong_to_site',
			CookieConsent::SITE_SETTINGS_TABLE,
			'site_id',
			Table::SITES,
			'id',
            'CASCADE',
            'CASCADE'
		);
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		$this->dropTableIfExists(CookieConsent::SITE_SETTINGS_TABLE);
		return true;
	}
}
