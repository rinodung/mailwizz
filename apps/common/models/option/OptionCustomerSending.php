<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSending
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class OptionCustomerSending extends OptionBase
{
    /**
     * Time units list
     */
    const TIME_UNIT_MINUTE = 'minute';
    const TIME_UNIT_HOUR = 'hour';
    const TIME_UNIT_DAY = 'day';
    const TIME_UNIT_WEEK = 'week';
    const TIME_UNIT_MONTH = 'month';
    const TIME_UNIT_YEAR = 'year';

    /**
     * Quota reached actions
     */
    const ACTION_QUOTA_REACHED_MOVE_IN_GROUP = 'move-in-group';
    const ACTION_QUOTA_REACHED_RESET         = 'reset';
    const ACTION_QUOTA_REACHED_NOTHING       = '';

    /**
     * @var int
     */
    public $quota = -1;

    /**
     * @var int
     */
    public $quota_time_value = -1;

    /**
     * @var string
     */
    public $quota_time_unit = 'month';

    /**
     * @var string
     */
    public $quota_wait_expire = self::TEXT_YES;

    /**
     * @var string
     */
    public $action_quota_reached = '';

    /**
     * @var int|null
     */
    public $move_to_group_id;

    /**
     * @var string
     */
    public $quota_notify_enabled = self::TEXT_NO;

    /**
     * @var string
     */
    public $quota_notify_email_content = '';

    /**
     * @var int
     */
    public $quota_notify_percent = 95;

    /**
     * @var int
     */
    public $hourly_quota = 0;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_sending';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['quota, quota_time_value, quota_time_unit, quota_wait_expire', 'required'],
            ['quota, quota_time_value', 'numerical', 'integerOnly' => true, 'min' => -1],
            ['quota_time_unit', 'in', 'range' => array_keys($this->getTimeUnits())],
            ['action_quota_reached', 'in', 'range' => array_keys($this->getActionsQuotaReached())],
            ['quota_wait_expire', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['move_to_group_id', 'numerical', 'integerOnly' => true],
            ['move_to_group_id', 'exist', 'className' => CustomerGroup::class, 'attributeName' => 'group_id'],

            ['quota_notify_enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['quota_notify_email_content', 'safe'],
            ['quota_notify_percent', 'numerical', 'min' => 50, 'max' => 98],

            ['hourly_quota', 'numerical', 'integerOnly' => true, 'min' => 0],
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
            'quota'                 => $this->t('Sending quota'),
            'quota_time_value'      => $this->t('Time value'),
            'quota_time_unit'       => $this->t('Time unit'),
            'quota_wait_expire'     => $this->t('Wait for quota to expire'),
            'action_quota_reached'  => $this->t('Action when quota reached'),
            'move_to_group_id'      => $this->t('Customer group'),

            'quota_notify_enabled'       => $this->t('Enable quota notifications'),
            'quota_notify_email_content' => $this->t('Email content'),
            'quota_notify_percent'       => $this->t('Sending percent notification'),

            'hourly_quota' => $this->t('Hourly quota'),
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
            'quota'                 => '',
            'quota_time_value'      => '',
            'quota_time_unit'       => '',
            'action_quota_reached'  => '',
            'move_to_group_id'      => '',
            'hourly_quota'          => 0,
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
            'quota'                 => $this->t('How many emails the customers are allowed to send for the specified "time value", set to -1 for unlimited'),
            'quota_time_value'      => $this->t('How many "time units" the quota is available if not consumed, set to -1 for unlimited. Please choose a value lower than the value you have set in Backend > Settings > Cron > Delivery servers logs removal days'),
            'quota_time_unit'       => $this->t('The time unit after which customers with remaining emails are denied sending'),
            'quota_wait_expire'     => $this->t('Whether to wait for the quota to expire when the sending quota has been reached'),
            'action_quota_reached'  => $this->t('What action to take when the sending quota is reached'),
            'move_to_group_id'      => $this->t('Move the customer into this group after the sending quota is reached'),

            'quota_notify_enabled'       => $this->t('Whether to enable the quota notifications so that customer get notified once they are close to reaching the sending quota'),
            'quota_notify_email_content' => $this->t('The email template for when the sending quota notifications are sent'),
            'quota_notify_percent'       => $this->t('The percent the sending quota has to be over in order to send the notification'),

            'hourly_quota' => $this->t('Maximum number of emails the customer is allowed to send in one hour. This is substracted from the overall sending quota'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getActionsQuotaReached(): array
    {
        return [
            self::ACTION_QUOTA_REACHED_NOTHING       => $this->t('Do nothing, customer will not be able to send more emails'),
            self::ACTION_QUOTA_REACHED_RESET         => $this->t('Reset the counters for a fresh start'),
            self::ACTION_QUOTA_REACHED_MOVE_IN_GROUP => $this->t('Move customer into a specific group'),
        ];
    }

    /**
     * @return array
     */
    public function getTimeUnits(): array
    {
        return [
            self::TIME_UNIT_MINUTE=> ucfirst(t('app', self::TIME_UNIT_MINUTE)),
            self::TIME_UNIT_HOUR  => ucfirst(t('app', self::TIME_UNIT_HOUR)),
            self::TIME_UNIT_DAY   => ucfirst(t('app', self::TIME_UNIT_DAY)),
            self::TIME_UNIT_WEEK  => ucfirst(t('app', self::TIME_UNIT_WEEK)),
            self::TIME_UNIT_MONTH => ucfirst(t('app', self::TIME_UNIT_MONTH)),
            self::TIME_UNIT_YEAR  => ucfirst(t('app', self::TIME_UNIT_YEAR)),
        ];
    }

    /**
     * @return array
     */
    public function getGroupsList(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }

        return $options = CustomerGroupCollection::findAll()->mapWithKeys(function (CustomerGroup $group) {
            return [$group->group_id => $group->name];
        })->all();
    }

    /**
     * @return bool
     */
    public function getActionQuotaWhenReachedIsMoveToGroup(): bool
    {
        return $this->action_quota_reached === self::ACTION_QUOTA_REACHED_MOVE_IN_GROUP;
    }

    /**
     * @return bool
     */
    public function getActionWhenQuotaReachedIsReset(): bool
    {
        return $this->action_quota_reached === self::ACTION_QUOTA_REACHED_RESET;
    }

    /**
     * @return bool
     */
    public function getActionWhenQuotaReachedIsNothing(): bool
    {
        return $this->action_quota_reached === self::ACTION_QUOTA_REACHED_NOTHING;
    }

    /**
     * @return int
     */
    public function getMoveToGroupId(): int
    {
        return (int)$this->move_to_group_id;
    }

    /**
     * @return int
     */
    public function getHourlyQuota(): int
    {
        return (int)$this->hourly_quota;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->getActionQuotaWhenReachedIsMoveToGroup() && empty($this->move_to_group_id)) {
            $this->move_to_group_id = -1; // not empty but still trigger validation
        }

        if (!$this->getActionQuotaWhenReachedIsMoveToGroup()) {
            $this->move_to_group_id = null;
        }

        return parent::beforeValidate();
    }
}
