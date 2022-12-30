<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessFeedbackLoopServers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class OptionCronProcessFeedbackLoopServers extends OptionBase
{
    /**
     * Action flags
     */
    const ACTION_DELETE_SUBSCRIBER = 'delete';
    const ACTION_UNSUBSCRIBE_SUBSCRIBER = 'unsubscribe';
    const ACTION_BLACKLIST_SUBSCRIBER = 'blacklist';

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
     * @var string
     */
    public $subscriber_action = 'unsubscribe';

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
    protected $_categoryName = 'system.cron.process_feedback_loop_servers';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['servers_at_once, emails_at_once, pause, subscriber_action, days_back', 'required'],
            ['servers_at_once, emails_at_once, pause', 'numerical', 'integerOnly' => true],
            ['servers_at_once', 'numerical', 'min' => 1, 'max' => 100],
            ['emails_at_once', 'numerical', 'min' => 100, 'max' => 1000],
            ['pause', 'numerical', 'min' => 0, 'max' => 60],
            ['days_back', 'numerical', 'min' => 0, 'max' => 3650],
            ['subscriber_action', 'in', 'range' => array_keys($this->getSubscriberActionOptions())],
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
            'subscriber_action'=> $this->t('Action against subscriber'),
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
            'subscriber_action'=> null,
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
            'subscriber_action'=> $this->t('Whether to unsubscribe, delete or blacklist the subscriber.'),
            'days_back'        => $this->t('Process emails that are newer than this amount of days. Increasing the number of days increases the amount of emails to be processed.'),
            'use_pcntl'        => $this->t('Whether to process using PCNTL, that is multiple processes in parallel.'),
            'pcntl_processes'  => $this->t('The number of processes to run in parallel.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getSubscriberActionOptions(): array
    {
        return [
            self::ACTION_DELETE_SUBSCRIBER          => ucwords($this->t(self::ACTION_DELETE_SUBSCRIBER)),
            self::ACTION_UNSUBSCRIBE_SUBSCRIBER     => ucwords($this->t(self::ACTION_UNSUBSCRIBE_SUBSCRIBER)),
            self::ACTION_BLACKLIST_SUBSCRIBER       => ucwords($this->t(self::ACTION_BLACKLIST_SUBSCRIBER)),
        ];
    }

    /**
     * @return string
     */
    public function getSubscriberAction(): string
    {
        return $this->subscriber_action ?: self::ACTION_UNSUBSCRIBE_SUBSCRIBER;
    }

    /**
     * @return bool
     */
    public function getSubscriberActionIsDelete(): bool
    {
        return $this->getSubscriberAction() === self::ACTION_DELETE_SUBSCRIBER;
    }

    /**
     * @return bool
     */
    public function getSubscriberActionIsUnsubscribe(): bool
    {
        return $this->getSubscriberAction() === self::ACTION_UNSUBSCRIBE_SUBSCRIBER;
    }

    /**
     * @return bool
     */
    public function getSubscriberActionIsBlacklist(): bool
    {
        return $this->getSubscriberAction() === self::ACTION_BLACKLIST_SUBSCRIBER;
    }

    /**
     * @param ListSubscriber $subscriber
     * @param Campaign $campaign
     *
     * @throws CException
     */
    public function takeActionAgainstSubscriberWithCampaign(ListSubscriber $subscriber, Campaign $campaign): void
    {
        if ($this->getSubscriberActionIsUnsubscribe()) {
            try {
                $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);
            } catch (Exception $e) {
            }

            $count = (int)CampaignTrackUnsubscribe::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if ($count === 0) {
                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = (int)$campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = (int)$subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->ip_address    = (string)request()->getUserHostAddress();
                $trackUnsubscribe->user_agent    = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
                $trackUnsubscribe->save(false);
            }

            $count = (int)CampaignComplainLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if ($count === 0) {
                $complaintLog = new CampaignComplainLog();
                $complaintLog->campaign_id      = (int)$campaign->campaign_id;
                $complaintLog->subscriber_id    = (int)$subscriber->subscriber_id;
                $complaintLog->message          = 'Abuse complaint!';
                $complaintLog->save(false);
            }

            return;
        }

        if ($this->getSubscriberActionIsBlacklist()) {
            $subscriber->addToBlacklist('Abuse complaint!');

            $count = CampaignComplainLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (empty($count)) {
                $complaintLog                = new CampaignComplainLog();
                $complaintLog->campaign_id   = (int)$campaign->campaign_id;
                $complaintLog->subscriber_id = (int)$subscriber->subscriber_id;
                $complaintLog->message       = 'Abuse complaint!';
                $complaintLog->save(false);
            }

            return;
        }

        if ($this->getSubscriberActionIsDelete()) {
            try {
                $subscriber->delete();
            } catch (Exception $e) {
            }
        }
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
    public function getDaysBack(): int
    {
        return (int)$this->days_back;
    }

    /**
     * @return int
     */
    public function getPcntlProcesses(): int
    {
        return (int)$this->pcntl_processes;
    }

    /**
     * @return bool
     */
    public function getUsePcntl(): bool
    {
        return $this->use_pcntl === self::TEXT_YES;
    }
}
