<?php

namespace elleracompany\cookieconsent\console;

use Craft;
use craft\helpers\Console;
use elleracompany\cookieconsent\records\Consent;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * CookieConsent Retention Commands
 *
 * @author    elleracompany
 * @package   cookie-consent
 * @since     1.3.0
 */
class RetentionController extends Controller
{
    /**
     * @var string|int|null Retention Days
     */
    public string|int|null $days = null;

    /**
     * @var string|int|null Site ID
     */
    public string|int|null $sid = null;

    /**
     * @var string|null Site handle
     */
    public ?string $handle = null;

    /**
     * @var int Default Retention Days
     */
    const DEFAULT_DAYS = 365;

    /**
     * Action to option mapping
     * @var array
     */
    public array $arguments = [
        'clear' => [
            'days',
            'sid',
            'handle'
        ]
    ];

    /**
     * @since 1.3.0
     *
     * @param string $actionID
     * @return string[]
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), $this->arguments[$actionID] ?? []);
    }

    /**
     * @inheritDoc
     */
    public function optionAliases(): array
    {
        return [
            'd' => 'days',
            's' => 'sid',
            'h' => 'handle'
        ];
    }

    /**
     * Delete old consents. Pass in -d or --days to alter the default 365 days retention
     *
     * @since 1.3.0
     */
    public function actionClear(): int
    {
        if ($this->days === null || !is_numeric($this->days)) {
            $this->days = self::DEFAULT_DAYS;
        }

        if ($this->sid !== null && $this->handle !== null) {
            $this->stderr("Please provide --sid _or_ --handle - not both.\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $site = null;
        if ($this->sid !== null) {
            if (!is_numeric($this->sid) || !($site = Craft::$app->getSites()->getSiteById((int) $this->sid))) {
                $this->stderr("Unknown site ID: {$this->sid}\n", Console::FG_RED);
                return ExitCode::USAGE;
            }
        } elseif ($this->handle !== null) {
            if (!($site = Craft::$app->getSites()->getSiteByHandle($this->handle))) {
                $this->stderr("Unknown site handle: {$this->handle}\n", Console::FG_RED);
                return ExitCode::USAGE;
            }
        }

        $this->stdout("Clearing consents older than {$this->days} days ", Console::FG_YELLOW);
        if ($site) {
            $this->stdout("from {$site->name} ({$site->id}) ", Console::FG_YELLOW);
        }
        $this->stdout("...\n", Console::FG_YELLOW);

        $threshold = date('Y-m-d H:i:s', time() - ((int) $this->days * 24 * 60 * 60));
        $condition = ['<', 'dateUpdated', $threshold];
        if ($site) {
            $condition = ['and', ['site_id' => $site->id], $condition];
        }
        $deleted = Consent::deleteAll($condition);

        $this->stdout("  Deleted {$deleted} records.\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
