<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronDelivery
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionCronDelivery extends OptionBase
{
    /**
     * @var int
     */
    public $campaigns_at_once = 10;

    /**
     * @var int
     */
    public $subscribers_at_once = 300;

    /**
     * @var int
     */
    public $change_server_at = 0;

    /**
     * @var string
     */
    public $use_pcntl = self::TEXT_NO;

    /**
     * @var int
     */
    public $campaigns_in_parallel = 5;

    /**
     * @var int
     */
    public $subscriber_batches_in_parallel = 5;

    /**
     * @var float
     */
    public $max_bounce_rate = -1;

    /**
     * @var int
     */
    public $max_complaint_rate = -1;

    /**
     * @var string
     */
    public $retry_failed_sending = self::TEXT_NO;

    /**
     * @var string
     */
    public $auto_adjust_campaigns_at_once = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.send_campaigns';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['campaigns_at_once, subscribers_at_once, change_server_at, auto_adjust_campaigns_at_once', 'required'],
            ['campaigns_at_once, subscribers_at_once, change_server_at', 'numerical', 'integerOnly' => true],
            ['campaigns_at_once', 'numerical', 'min' => 1, 'max' => 10000],
            ['subscribers_at_once', 'numerical', 'min' => 1, 'max' => 10000],
            ['change_server_at', 'numerical', 'min' => 0, 'max' => 10000],

            // since 1.3.5.9
            ['use_pcntl', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['campaigns_in_parallel, subscriber_batches_in_parallel', 'numerical', 'min' => 1, 'max' => 1000],
            ['max_bounce_rate, max_complaint_rate', 'numerical', 'min' => -1, 'max' => 100],

            // since 1.4.4
            ['retry_failed_sending', 'in', 'range' => array_keys($this->getYesNoOptions())],

            // since 1.5.3
            ['auto_adjust_campaigns_at_once', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'campaigns_at_once'     => $this->t('Campaigns at once'),
            'subscribers_at_once'   => $this->t('Subscribers at once'),
            'change_server_at'      => $this->t('Change server at'),

            // since 1.3.5.9
            'use_pcntl'                     => $this->t('Parallel sending via PCNTL'),
            'campaigns_in_parallel'         => $this->t('Campaigns in parallel'),
            'subscriber_batches_in_parallel'=> $this->t('Subscriber batches in parallel'),
            'max_bounce_rate'               => $this->t('Max. bounce rate'),
            'max_complaint_rate'            => $this->t('Max. complaint rate'),

            // since 1.4.4
            'retry_failed_sending'          => $this->t('Retry failed sendings'),

            // since 1.5.3
            'auto_adjust_campaigns_at_once' => $this->t('Adjust campaigns at once'),
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
            'campaigns_at_once'     => null,
            'subscribers_at_once'   => null,
            'change_server_at'      => null,

            // since 1.3.5.9
            'campaigns_in_parallel'         => 5,
            'subscriber_batches_in_parallel'=> 5,
            'max_bounce_rate'               => -1,
            'max_complaint_rate'            => -1,
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
            'campaigns_at_once'     => $this->t('How many campaigns to process at once.'),
            'subscribers_at_once'   => $this->t('How many subscribers to process at once for each loaded campaign.'),
            'change_server_at'      => $this->t('After how many sent emails we should change the delivery server. This only applies if there are multiple delivery servers. Set this to 0 to disable it.'),

            // since 1.3.5.9
            'use_pcntl'                     => $this->t('The PHP PCNTL extension allows processing campaigns in parallel. You can enable it if you need your campaigns to be sent faster.'),
            'campaigns_in_parallel'         => $this->t('How many campaigns to send in parallel. Please note that this depends on the number of campaigns at once.'),
            'subscriber_batches_in_parallel'=> $this->t('How many batches of subscribers to send at once. Please note that this depends on the number of subscribers at once.'),
            'max_bounce_rate'               => $this->t('When a campaign reaches this bounce rate, it will be blocked. Set to -1 to disable this check or between 1 and 100 to set the percent of allowed bounce rate.'),
            'max_complaint_rate'            => $this->t('When a campaign reaches this complaint rate, it will be blocked. Set to -1 to disable this check or between 1 and 100 to set the percent of allowed complaint rate.'),

            // since 1.4.4
            'retry_failed_sending'          => $this->t('By default, when sending a campaign, if sending to a certain email address fails, we giveup on that email address and move forward. This option allows you to enable retry sending for failed emails up to 3 times.'),

            // since 1.5.3
            'auto_adjust_campaigns_at_once' => $this->t('Whether the system should try and automtically adjust and optimize the number of campaigns at once.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getCampaignsAtOnce(): int
    {
        return (int)$this->campaigns_at_once;
    }

    /**
     * @return int
     */
    public function getSubscribersAtOnce(): int
    {
        return (int)$this->subscribers_at_once;
    }

    /**
     * @return int
     */
    public function getChangeServerAt(): int
    {
        return (int)$this->change_server_at;
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
    public function getCampaignsInParallel(): int
    {
        return (int)$this->campaigns_in_parallel;
    }

    /**
     * @return int
     */
    public function getSubscriberBatchesInParallel(): int
    {
        return (int)$this->subscriber_batches_in_parallel;
    }

    /**
     * @return float
     */
    public function getMaxBounceRate(): float
    {
        return (float)$this->max_bounce_rate;
    }

    /**
     * @return float
     */
    public function getMaxComplaintRate(): float
    {
        return (float)$this->max_complaint_rate;
    }

    /**
     * @return bool
     */
    public function getRetryFailedSending(): bool
    {
        return $this->retry_failed_sending === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getAutoAdjustCampaignsAtOnce(): bool
    {
        return $this->auto_adjust_campaigns_at_once === self::TEXT_YES;
    }
}
