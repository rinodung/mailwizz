<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessDeliveryBounce
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class OptionCronProcessDeliveryBounce extends OptionBase
{
    /**
     * @var int
     */
    public $process_at_once = 100;

    /**
     * @var int
     */
    public $max_fatal_errors = 1;

    /**
     * @var int
     */
    public $max_soft_errors = 5;

    /**
     * @var int
     */
    public $max_hard_bounce = 1;

    /**
     * @var int
     */
    public $max_soft_bounce = 5;

    /**
     * @var int
     */
    public $delivery_servers_usage_logs_removal_days = 90;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.process_delivery_bounce';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['process_at_once, max_fatal_errors, max_soft_errors, max_hard_bounce, max_soft_bounce, delivery_servers_usage_logs_removal_days', 'required'],
            ['process_at_once, max_fatal_errors, max_soft_errors, max_hard_bounce, max_soft_bounce', 'numerical', 'integerOnly' => true],
            ['process_at_once', 'numerical', 'min' => 50, 'max' => 10000],
            ['max_fatal_errors, max_hard_bounce', 'numerical', 'min' => 1, 'max' => 10000],
            ['max_soft_errors, max_soft_bounce', 'numerical', 'min' => 1, 'max' => 10000],
            ['delivery_servers_usage_logs_removal_days', 'numerical', 'min' => 1, 'max' => 10000],
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
            'process_at_once'   => $this->t('Process at once'),
            'max_fatal_errors'  => $this->t('Max. fatal errors'),
            'max_hard_bounce'   => $this->t('Max. hard bounce'),
            'max_soft_errors'   => $this->t('Max. soft errors'),
            'max_soft_bounce'   => $this->t('Max. soft bounce'),

            'delivery_servers_usage_logs_removal_days' => $this->t('Delivery servers logs removal days'),
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
            'process_at_once'   => '',
            'max_fatal_errors'  => '',
            'max_hard_bounce'   => '',
            'max_soft_errors'   => '',
            'max_soft_bounce'   => '',
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
            'process_at_once'   => $this->t('How many logs to process at once. Please note that this number will be 4 times higher on the server.'),
            'max_fatal_errors'  => $this->t('Maximum allowed number of fatal errors a subscriber is allowed to have while we try to deliver the email.'),
            'max_hard_bounce'   => $this->t('Maximum allowed number of hard bounces a subscriber is allowed to have after we delivered the email.'),
            'max_soft_errors'   => $this->t('Maximum allowed number of soft errors a subscriber is allowed to have while we try to deliver the email.'),
            'max_soft_bounce'   => $this->t('Maximum allowed number of soft bounces a subscriber is allowed to have after we delivered the email.'),

            'delivery_servers_usage_logs_removal_days' => $this->t('The number of days to keep the delivery server logs in the system. Please note that these logs are used for customer quota calculation so you have to keep them for at least the largest number of days used in customer sending quota.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getDeliveryServersUsageLogsRemovalDays(): int
    {
        return (int)$this->delivery_servers_usage_logs_removal_days;
    }

    /**
     * @return int
     */
    public function getProcessAtOnce(): int
    {
        return (int)$this->process_at_once;
    }

    /**
     * @return int
     */
    public function getMaxFatalErrors(): int
    {
        return (int)$this->max_fatal_errors;
    }

    /**
     * @return int
     */
    public function getMaxSoftErrors(): int
    {
        return (int)$this->max_soft_errors;
    }

    /**
     * @return int
     */
    public function getMaxHardBounce(): int
    {
        return (int)$this->max_hard_bounce;
    }

    /**
     * @return int
     */
    public function getMaxSoftBounce(): int
    {
        return (int)$this->max_soft_bounce;
    }
}
