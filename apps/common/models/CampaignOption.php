<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOption
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3
 */

/**
 * This is the model class for table "campaign_option".
 *
 * The followings are the available columns in table 'campaign_option':
 * @property integer|null $campaign_id
 * @property string $url_tracking
 * @property string $open_tracking
 * @property string $json_feed
 * @property string $xml_feed
 * @property string $embed_images
 * @property string $plain_text_email
 * @property string $autoresponder_event
 * @property string $autoresponder_time_unit
 * @property integer $autoresponder_time_value
 * @property string $autoresponder_include_imported
 * @property string $autoresponder_include_current
 * @property string $autoresponder_time_min_hour
 * @property string $autoresponder_time_min_minute
 * @property string $autoresponder_open_campaign_id
 * @property string $autoresponder_sent_campaign_id
 * @property string $email_stats
 * @property int $email_stats_sent
 * @property int $email_stats_delay_days
 * @property string $cronjob
 * @property int $cronjob_enabled
 * @property int $cronjob_max_runs
 * @property int $cronjob_runs_counter
 * @property string $cronjob_rescheduled
 * @property string $blocked_reason
 * @property int $giveup_counter
 * @property int $giveup_count
 * @property string $max_send_count
 * @property string $max_send_count_random
 * @property integer $tracking_domain_id
 * @property string $preheader
 * @property string $forward_friend_subject
 * @property string $timewarp_enabled
 * @property integer $timewarp_hour
 * @property integer $timewarp_minute
 * @property string $share_reports_enabled
 * @property string $share_reports_password
 * @property int $processed_count
 * @property int $delivery_success_count
 * @property int $delivery_error_count
 * @property int $industry_processed_count
 * @property int $bounces_count
 * @property int $hard_bounces_count
 * @property int $soft_bounces_count
 * @property int $internal_bounces_count
 * @property int $opens_count
 * @property int $unique_opens_count
 * @property int $clicks_count
 * @property int $unique_clicks_count
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property Campaign $autoresponderOpenCampaign
 * @property Campaign $autoresponderSentCampaign
 * @property Campaign $regularOpenUnopen
 * @property TrackingDomain $trackingDomain
 */
class CampaignOption extends ActiveRecord
{
    /**
     * Autoresponder events
     */
    const AUTORESPONDER_EVENT_AFTER_SUBSCRIBE     = 'AFTER-SUBSCRIBE';
    const AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN = 'AFTER-CAMPAIGN-OPEN';
    const AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT = 'AFTER-CAMPAIGN-SENT';

    /**
     * Autoresponder time units
     */
    const AUTORESPONDER_TIME_UNIT_MINUTE = 'minute';
    const AUTORESPONDER_TIME_UNIT_HOUR   = 'hour';
    const AUTORESPONDER_TIME_UNIT_DAY    = 'day';
    const AUTORESPONDER_TIME_UNIT_WEEK   = 'week';
    const AUTORESPONDER_TIME_UNIT_MONTH  = 'month';
    const AUTORESPONDER_TIME_UNIT_YEAR   = 'year';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_option}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['url_tracking, open_tracking, json_feed, xml_feed, embed_images, plain_text_email', 'required'],
            ['url_tracking, open_tracking, json_feed, xml_feed, embed_images, plain_text_email', 'length', 'max' => 3],
            ['url_tracking, open_tracking, json_feed, xml_feed, embed_images, plain_text_email', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['email_stats, preheader, forward_friend_subject', 'length', 'max' => 255],

            ['autoresponder_event, autoresponder_time_unit, autoresponder_time_value, autoresponder_include_imported, autoresponder_include_current', 'required', 'on' => 'step-confirm-ar'],
            ['autoresponder_event', 'in', 'range' => array_keys($this->getAutoresponderEvents())],
            ['autoresponder_time_unit', 'in', 'range' => array_keys($this->getAutoresponderTimeUnits())],
            ['autoresponder_time_value', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 3650],
            ['autoresponder_include_imported, autoresponder_include_current', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['autoresponder_open_campaign_id', 'numerical', 'integerOnly' => true, 'min' => 0],
            ['autoresponder_sent_campaign_id', 'numerical', 'integerOnly' => true, 'min' => 0],
            ['autoresponder_time_min_hour', 'in', 'range' => array_keys($this->getAutoresponderTimeMinHoursList())],
            ['autoresponder_time_min_minute', 'in', 'range' => array_keys($this->getAutoresponderTimeMinMinutesList())],
            ['autoresponder_time_min_hour, autoresponder_time_min_minute', '_validateAutoresponderTimeMin'],

            // since 1.3.6.3
            ['max_send_count', 'length', 'max' => 11],
            ['max_send_count', 'numerical', 'integerOnly' => true, 'min' => 0],
            ['max_send_count_random', 'in', 'range' => [self::TEXT_NO, self::TEXT_YES]],

            // since 1.3.6.6
            ['tracking_domain_id', 'length', 'max' => 11],
            ['tracking_domain_id', 'numerical', 'integerOnly' => true, 'min' => 0],
            ['tracking_domain_id', 'exist', 'className' => TrackingDomain::class, 'attributeName' => 'domain_id'],

            // since 1.3.7.3
            ['share_reports_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['share_reports_password', 'length', 'min' => 4, 'max' => 64],

            // since 1.3.9.2
            ['share_reports_mask_email_addresses', 'in', 'range' => array_keys($this->getYesNoOptions())],

            // since 1.3.9.5
            ['cronjob_max_runs', 'numerical', 'integerOnly' => true, 'min' => -1, 'max' => 10000000],
            ['cronjob_runs_counter', 'numerical', 'integerOnly' => true, 'min' => 0],

            // since 1.5.2
            ['timewarp_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['timewarp_hour', 'in', 'range' => array_keys($this->getTimewarpHours())],
            ['timewarp_minute', 'in', 'range' => array_keys($this->getTimewarpMinutes())],

            // since 2.1.2
            ['email_stats_delay_days', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 30],

        ];

        // since 1.3.5.3
        $rules[] = ['cronjob', '_validateCronExpression'];
        $rules[] = ['cronjob_enabled', 'in', 'range' => [0, 1]];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'                   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'autoresponderOpenCampaign'  => [self::BELONGS_TO, Campaign::class, 'autoresponder_open_campaign_id'],
            'autoresponderSentCampaign'  => [self::BELONGS_TO, Campaign::class, 'autoresponder_sent_campaign_id'],
            'trackingDomain'             => [self::BELONGS_TO, TrackingDomain::class, 'tracking_domain_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'campaign_id'        => t('campaigns', 'Campaign'),
            'url_tracking'       => t('campaigns', 'Url tracking'),
            'open_tracking'      => t('campaigns', 'Open tracking'),
            'json_feed'          => t('campaigns', 'Json feed'),
            'xml_feed'           => t('campaigns', 'Xml feed'),
            'embed_images'       => t('campaigns', 'Embed images'),
            'plain_text_email'   => t('campaigns', 'Plain text email'),

            'email_stats'             => t('campaigns', 'Email stats'),
            'email_stats_delay_days'  => t('campaigns', 'Email stats delay days'),

            'autoresponder_event'            => t('campaigns', 'Autoresponder event'),
            'autoresponder_time_unit'        => t('campaigns', 'Autoresponder time unit'),
            'autoresponder_time_value'       => t('campaigns', 'Autoresponder time value'),
            'autoresponder_include_imported' => t('campaigns', 'Incl. imported subscribers'),
            'autoresponder_include_current'  => t('campaigns', 'Incl. current subscribers'),
            'autoresponder_open_campaign_id' => t('campaigns', 'Send when opening this campaign'),
            'autoresponder_sent_campaign_id' => t('campaigns', 'Send after sending this campaign'),
            'autoresponder_time_min_hour'    => t('campaigns', 'Send only at/after this time'),
            'autoresponder_time_min_minute'  => t('campaigns', 'Send only at/after this time'),

            'cronjob'         => t('campaigns', 'Advanced recurring'),
            'cronjob_enabled' => t('campaigns', 'Enabled'),

            'max_send_count'         => t('campaigns', 'Max. subscribers'),
            'max_send_count_random'  => t('campaigns', 'Randomize subscribers'),
            'tracking_domain_id'     => t('campaigns', 'Tracking domain'),
            'preheader'              => t('campaigns', 'Preheader'),
            'forward_friend_subject' => t('campaigns', 'Forward friend subject'),

            'share_reports_enabled'    => t('campaigns', 'Enable sharing'),
            'share_reports_password'   => t('campaigns', 'Password'),
            'share_reports_mask_email_addresses' => t('campaigns', 'Mask emails'),

            'cronjob_max_runs'     => t('campaigns', 'Max. runs'),
            'cronjob_runs_counter' => t('campaigns', 'Max. runs counter'),

            'timewarp_enabled'  => t('campaigns', 'Enable timewarp'),
            'timewarp_hour'     => t('campaigns', 'Timewarp hour'),
            'timewarp_minute'     => t('campaigns', 'Timewarp minute'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignOption the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignOption $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'url_tracking'      => t('campaigns', 'Whether to enable url tracking'),
            'open_tracking'     => t('campaigns', 'Whether to enable opens tracking'),
            'json_feed'         => t('campaigns', 'Whether your campaign will parse a {feedType} feed and dynamically insert content from the feed into template', ['{feedType}' => 'json']),
            'xml_feed'          => t('campaigns', 'Whether your campaign will parse a {feedType} feed and dynamically insert content from the feed into template', ['{feedType}' => 'xml(rss)']),
            'embed_images'      => t('campaigns', 'Whether to embed images in the template instead of loading them remotely'),
            'plain_text_email'  => t('campaigns', 'Whether to generate the plain text version of the campaign email based on your html email version'),

            'email_stats'             => t('campaigns', 'Where to send the campaign stats when it finish sending, separate multiple email addresses by a comma. Leave empty to not send the stats'),
            'email_stats_delay_days'  => t('campaigns', 'How many days to wait to send second email with campaign stats. First one is sent right after the campaign finishes sending'),

            'autoresponder_event'            => t('campaigns', 'The event timing that will trigger this autoresponder'),
            'autoresponder_time_unit'        => t('campaigns', 'The time unit for this autoresponder'),
            'autoresponder_time_value'       => t('campaigns', 'Based on the time unit, how much to wait until this autoresponder gets sent. 0 means it will be sent immediatly after event'),
            'autoresponder_include_imported' => t('campaigns', 'Whether to include imported subscribers into this autoresponder'),
            'autoresponder_include_current'  => t('campaigns', 'Whether to include current subscribers into this autoresponder. By default the AR is sent only to new subscribers'),
            'autoresponder_open_campaign_id' => t('campaigns', 'Which campaign must be opened in order to trigger this autoresponder'),
            'autoresponder_sent_campaign_id' => t('campaigns', 'Which campaign must be sent in order to trigger this autoresponder'),
            'autoresponder_time_min_hour'    => t('campaigns', 'Send the autoresonder no earlier than this time in the day. Time is UTC 00:00, take into consideration your timezone offset. Current UTC time is: {time}', ['{time}' => date('H:i:s')]),
            'autoresponder_time_min_minute'  => t('campaigns', 'Send the autoresonder no earlier than this time in the day. Time is UTC 00:00, take into consideration your timezone offset. Current UTC time is: {time}', ['{time}' => date('H:i:s')]),

            'max_send_count'        => t('campaigns', 'Whether to send only to this number of subscribers instead of sending to the whole list'),
            'max_send_count_random' => t('campaigns', 'If you limit the number of subscribers to which this campaigns goes to, enabling this option will pick them randomly from the list'),
            'tracking_domain_id'    => t('campaigns', 'The domain that will be used for tracking purposes, must be a DNS CNAME of the master domain.'),
            'preheader'             => t('campaigns', 'A preheader is the short summary text that follows the subject line when an email is viewed in the inbox. Many mobile, desktop and web email clients provide them to tip you off on what the email contains before you open it'),
            'forward_friend_subject'=> t('campaigns', 'The subject line for the email sent when a subscriber will forward this campaign to a friend. Leave empty to use the default: {subject}', [
                '{subject}' => $this->getDefaultForwardFriendSubject(),
            ]),

            'share_reports_enabled'    => t('campaigns', 'Whether to allow campaign reports sharing'),
            'share_reports_password'   => t('campaigns', 'The password for accessing the reports'),
            'share_reports_mask_email_addresses' => t('campaigns', 'Whether to mask the email addresses'),

            'cronjob_max_runs'     => t('campaigns', 'The maximum number of times this campaigns is allowed to send. Set to -1 for unlimited'),

            'timewarp_enabled'  => t('campaigns', 'Send the campaign according to the subscriber timezone. For example, the campaign will send at different hours for a US subscriber versus a subscriber from EU and they will get it at their local time, regardless of their time difference. For best results, make sure you schedule the campaign with at least 24 hours in advance.'),
            'timewarp_hour'     => t('campaigns', 'The hour when the subscriber should receive this campaign according to its own timezone.'),
            'timewarp_minute'   => t('campaigns', 'The minute when the subscriber should receive this campaign according to its own timezone.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getAutoresponderEvents(): array
    {
        return [
            self::AUTORESPONDER_EVENT_AFTER_SUBSCRIBE     => t('campaigns', self::AUTORESPONDER_EVENT_AFTER_SUBSCRIBE),
            self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN => t('campaigns', self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN),
            self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT => t('campaigns', self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT),
        ];
    }

    /**
     * @return array
     */
    public function getAutoresponderTimeUnits(): array
    {
        return [
            self::AUTORESPONDER_TIME_UNIT_MINUTE    => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_MINUTE)),
            self::AUTORESPONDER_TIME_UNIT_HOUR      => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_HOUR)),
            self::AUTORESPONDER_TIME_UNIT_DAY       => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_DAY)),
            self::AUTORESPONDER_TIME_UNIT_WEEK      => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_WEEK)),
            self::AUTORESPONDER_TIME_UNIT_MONTH     => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_MONTH)),
            self::AUTORESPONDER_TIME_UNIT_YEAR      => ucfirst(t('app', self::AUTORESPONDER_TIME_UNIT_YEAR)),
        ];
    }

    /**
     * @param string $name
     * @return string
     */
    public function getAutoresponderEventName(string $name = '')
    {
        if (empty($name)) {
            $name = (string)$this->autoresponder_event;
        }
        return $this->getAutoresponderEvents()[$name] ?? $name;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateCronExpression(string $attribute, array $params = []): void
    {
        if ($this->hasErrors() || $this->campaign->getIsAutoresponder() || !$this->cronjob_enabled || empty($this->$attribute)) {
            return;
        }

        if (empty($this->campaign) || !$this->campaign->getIsRegular()) {
            $this->addError($attribute, t('campaigns', 'No valid assigned campaign!'));
            return;
        }

        try {

            // 1.3.7.1
            $this->$attribute = trim((string)$this->$attribute);
            if (substr($this->$attribute, 0, 1) == '*') {
                $this->$attribute = substr_replace($this->$attribute, '0', 0, 1);
            }
            //

            $cron = new Cron\CronExpression($this->$attribute);
            $cron = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->addError($attribute, $e->getMessage());
        }
    }

    /**
     * @param string $reason
     * @return bool
     */
    public function setBlockedReason(string $reason = ''): bool
    {
        if (empty($this->campaign_id)) {
            return false;
        }

        $reason = StringHelper::truncateLength($reason, 255);

        db()->createCommand()->update(
            $this->tableName(),
            ['blocked_reason' => $reason],
            'campaign_id = :cid',
            [':cid' => (int)$this->campaign_id]
        );
        $this->blocked_reason = $reason;

        return true;
    }

    /**
     * @param int $by
     */
    public function updateSendingGiveupCounter(int $by = 1): void
    {
        if ((int)$by <= 0) {
            $this->giveup_counter = 0;
            self::model()->updateAll([
                'giveup_counter' => 0,
            ], 'campaign_id = :cid', [':cid' => (int)$this->campaign_id]);
            return;
        }

        $this->giveup_counter = (int)$this->giveup_counter + (int)$by;
        self::model()->updateCounters([
            'giveup_counter' => (int)$by,
        ], 'campaign_id = :cid', [':cid' => (int)$this->campaign_id]);
    }

    /**
     * @param int $count
     */
    public function updateSendingGiveupCount(int $count): void
    {
        if ((int)$this->giveup_count === (int)$count) {
            return;
        }

        $this->giveup_count = (int)$count;
        self::model()->updateAll([
            'giveup_count' => (int)$count,
        ], 'campaign_id = :cid', [':cid' => (int)$this->campaign_id]);
    }

    /**
     * @return bool
     */
    public function getCanSetMaxSendCount(): bool
    {
        return $this->campaign->getIsRegular() && !empty($this->max_send_count) && $this->max_send_count > 0;
    }

    /**
     * @return bool
     */
    public function getCanSetMaxSendCountRandom(): bool
    {
        return $this->getCanSetMaxSendCount() &&
               !empty($this->max_send_count_random) &&
               (string)$this->max_send_count_random === 'yes';
    }

    /**
     * @return array
     */
    public function getTrackingDomainsArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'domain_id, name';
        $criteria->compare('verified', TrackingDomain::TEXT_YES);

        if ($this->campaign_id && $this->campaign->customer_id) {
            $criteria->compare('customer_id', (int)$this->campaign->customer_id);
        }

        $criteria->order = 'domain_id DESC';
        $models = TrackingDomain::model()->findAll($criteria);

        $_options[''] = t('app', 'Choose');
        foreach ($models as $model) {
            $_options[$model->domain_id] = $model->name;
        }

        return $_options;
    }

    /**
     * @return bool
     */
    public function getAutoresponderIncludeImported(): bool
    {
        return (string)$this->autoresponder_include_imported === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getAutoresponderIncludeCurrent()
    {
        return (string)$this->autoresponder_include_current === self::TEXT_YES;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateAutoresponderTimeMin(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if (empty($this->autoresponder_time_min_hour) && empty($this->autoresponder_time_min_minute)) {
            return;
        }

        if (empty($this->autoresponder_time_min_hour) && !empty($this->autoresponder_time_min_minute)) {
            $this->addError('autoresponder_time_min_hour', t('campaigns', 'Please provide a valid hour!'));
            return;
        }

        if (!empty($this->autoresponder_time_min_hour) && empty($this->autoresponder_time_min_minute)) {
            $this->addError('autoresponder_time_min_minute', t('campaigns', 'Please provide a valid minute!'));
            return;
        }
    }

    /**
     * @return array
     */
    public function getAutoresponderTimeMinHoursList(): array
    {
        $list = [];
        for ($i = 0; $i < 24; $i++) {
            $n = (string)$i;
            if ($n < 10) {
                $n = '0' . $n;
            }
            $list[$n] = $n;
        }
        return $list;
    }

    /**
     * @return array
     */
    public function getAutoresponderTimeMinMinutesList(): array
    {
        $list = [];
        for ($i = 0; $i < 60; $i++) {
            $n = (string)$i;
            if ($n < 10) {
                $n = '0' . $n;
            }
            $list[$n] = $n;
        }
        return $list;
    }

    /**
     * @return string
     */
    public function getAutoresponderTimeMinHourMinute(): string
    {
        if (empty($this->autoresponder_time_min_hour) || empty($this->autoresponder_time_min_minute)) {
            return '';
        }

        return sprintf('%s:%s', $this->autoresponder_time_min_hour, $this->autoresponder_time_min_minute);
    }

    /**
     * @return bool
     */
    public function getTimewarpEnabled(): bool
    {
        return !empty($this->timewarp_enabled) && (string)$this->timewarp_enabled === 'yes';
    }

    /**
     * @return array
     */
    public function getTimewarpHours(): array
    {
        $list = [];
        for ($i = 0; $i <= 23; $i++) {
            $n = (string)$i;
            if ($n < 10) {
                $n = '0' . $n;
            }
            $list[$i] = $n;
        }
        return $list;
    }

    /**
     * @return array
     */
    public function getTimewarpMinutes(): array
    {
        $list = [];
        for ($i = 0; $i <= 59; $i++) {
            $n = (string)$i;
            if ($n < 10) {
                $n = '0' . $n;
            }
            $list[$i] = $n;
        }
        return $list;
    }

    /**
     * @return string
     */
    public function getDefaultForwardFriendSubject(): string
    {
        return t('campaigns', 'Hey, check out this url, I think you will like it.');
    }

    /**
     * @return string
     */
    public function getForwardFriendSubject(): string
    {
        if (empty($this->forward_friend_subject)) {
            return $this->getDefaultForwardFriendSubject();
        }

        return (string)$this->forward_friend_subject;
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        if ($this->autoresponder_event === self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN && empty($this->autoresponder_open_campaign_id)) {
            $this->addError('autoresponder_open_campaign_id', t('campaigns', 'Please select a campaign for this autoresponder!'));
        }

        if ($this->autoresponder_event === self::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT && empty($this->autoresponder_sent_campaign_id)) {
            $this->addError('autoresponder_sent_campaign_id', t('campaigns', 'Please select a campaign for this autoresponder!'));
        }
        parent::afterValidate();
    }
}
