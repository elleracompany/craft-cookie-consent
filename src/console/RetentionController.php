<?php


namespace elleracompany\cookieconsent\console;

use Craft;
use craft\helpers\Console;
use elleracompany\cookieconsent\records\Consent;
use yii\console\Controller;

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
     * @var string Retention Days
     */
    public $days;

    /**
     * @var string Site ID
     */
    public $sid;

    /**
     * @var string Site handle
     */
    public $handle;

    /**
     * @var integer Default Retention Days
     */
    const DEFAULT_DAYS = 365;

    /**
     * Argument to action mapping
     * @var array
     */
    public $arguments = [
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
     * @return array|string[]
     */
    public function options($actionID)
    {
        if(isset($this->arguments[$actionID]))
        {
            return array_merge(parent::options($actionID), $this->arguments[$actionID]);
        }
        else return parent::options($actionID);
    }
    /**
     * @inheritDoc
     */
    public function optionAliases()
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
    public function actionClear()
    {
        // Check if the days parameter if set and valid
        if(!isset($this->days) || !is_numeric($this->days)) $this->days = self::DEFAULT_DAYS;

        $this->stdout("Clearing consents older than {$this->days} days ", Console::FG_YELLOW);

        // Create the query
        $query = Consent::find();

        if(isset($this->sid) && isset($this->handle))
        {
            $this->stdout("  Please provide --sid _or_ --handle - not both.\n", Console::FG_RED);
            exit(1);
        }

        if(isset($this->sid) && is_numeric($this->sid))
        {
            $site = Craft::$app->getSites()->getSiteById($this->sid);
            if($site)
            {
                $query->where(['site_id' => $site->id]);
                $this->stdout("from {$site->name} ({$site->id}) ", Console::FG_YELLOW);
            }
        }

        if(isset($this->handle))
        {
            $site = Craft::$app->getSites()->getSiteByHandle($this->handle);
            if($site)
            {
                $query->where(['site_id' => $site->id]);
                $this->stdout("from {$site->name} ({$site->id}) ", Console::FG_YELLOW);
            }
        }
        $this->stdout("...\n", Console::FG_YELLOW);

        $timestamp = time() - ($this->days*24*60*60);
        $to_delete = $query->andWhere(['<', 'dateUpdated', date('Y-m-d H:i:s', $timestamp)])->all();

        $deleted = 0;
        $failed = 0;

        foreach ($to_delete as $del)
        {
            if($del->delete()) $deleted++;
            else $failed++;
        }
        if($failed > 0) $this->stdout("  Failed to delete {$failed} records.\n", Console::FG_RED);
        $this->stdout("  Deleted {$deleted} records.\n", Console::FG_GREEN);
    }
}