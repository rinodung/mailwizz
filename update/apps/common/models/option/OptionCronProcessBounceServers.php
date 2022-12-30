<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessBounceServers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class OptionCronProcessBounceServers extends OptionBase
{
    /**
     * @var int
     */
    public $servers_at_once = 10;

    /**
     * @var int
     */
    public $emails_at_once = 500;

    /**
     * @var int
     */
    public $pause = 5;

    /**
     * @var int
     */
    public $days_back = 3;

    /**
     * @var string
     */
    public $use_pcntl = self::TEXT_YES;

    /**
     * @var int
     */
    public $pcntl_processes = 10;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.process_bounce_servers';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['servers_at_once, emails_at_once, pause, days_back', 'required'],
            ['servers_at_once, emails_at_once, pause', 'numerical', 'integerOnly' => true],
            ['servers_at_once', 'numerical', 'min' => 1, 'max' => 100],
            ['emails_at_once', 'numerical', 'min' => 100, 'max' => 1000],
            ['pause', 'numerical', 'min' => 0, 'max' => 60],
            ['days_back', 'numerical', 'min' => 0, 'max' => 3650],
            ['use_pcntl', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['pcntl_processes', 'numerical', 'min' => 1, 'max' => 100],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'servers_at_once'  => $this->t('Servers at once'),
            'emails_at_once'   => $this->t('Emails at once'),
            'pause'            => $this->t('Pause'),
            'days_back'        => $this->t('Days back'),
            'use_pcntl'        => $this->t('Parallel processing via PCNTL'),
            'pcntl_processes'  => $this->t('Parallel processes count'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'servers_at_once'  => null,
            'emails_at_once'   => null,
            'pause'            => null,
            'days_back'        => 3,
            'pcntl_processes'  => 10,
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'servers_at_once'  => $this->t('How many servers to process at once.'),
            'emails_at_once'   => $this->t('How many emails for each server to process at once.'),
            'pause'            => $this->t('How many seconds to sleep after processing the emails from a server.'),
            'days_back'        => $this->t('Process emails that are newer than this amount of days. Increasing the number of days increases the amount of emails to be processed.'),
            'use_pcntl'        => $this->t('Whether to process using PCNTL, that is multiple processes in parallel.'),
            'pcntl_processes'  => $this->t('The number of processes to run in parallel.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getEmailsAtOnce(): int
    {
        return (int)$this->emails_at_once;
    }

    /**
     * @return int
     */
    public function getServersAtOnce(): int
    {
        return (int)$this->servers_at_once;
    }

    /**
     * @return int
     */
    public function getDaysBack(): int
    {
        return (int)$this->days_back;
    }

    /**
     * @return bool
     */
    public function getUsePcntl(): bool
    {
        return $this->use_pcntl === self::TEXT_YES;
    }

    /**
     * @return int
     */
    public function getPcntlProcesses(): int
    {
        return (int)$this->pcntl_processes;
    }
}
