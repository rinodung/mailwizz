<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessSubscribers
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

class OptionCronProcessSubscribers extends OptionBase
{
    /**
     * @var int
     */
    public $unsubscribe_days = 0;

    /**
     * @var int
     */
    public $unconfirm_days = 3;

    /**
     * @var int
     */
    public $blacklisted_days = 0;

    /**
     * @var string
     */
    public $sync_custom_fields_values = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.process_subscribers';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['unsubscribe_days, unconfirm_days, blacklisted_days', 'required'],
            ['unsubscribe_days, unconfirm_days, blacklisted_days', 'numerical', 'min' => 0, 'max' => 365],
            ['sync_custom_fields_values', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'unsubscribe_days'          => $this->t('Unsubscribe days'),
            'unconfirm_days'            => $this->t('Unconfirm days'),
            'blacklisted_days'          => $this->t('Blacklisted days'),
            'sync_custom_fields_values' => $this->t('Custom fields sync'),
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
            'unsubscribe_days'  => null,
            'unconfirm_days'    => null,
            'blacklisted_days'  => null,
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
            'unsubscribe_days'          => $this->t('How many days to keep the unsubscribers in the system. 0 is unlimited'),
            'unconfirm_days'            => $this->t('How many days to keep the unconfirmed subscribers in the system. 0 is unlimited'),
            'blacklisted_days'          => $this->t('How many days to keep the blacklisted subscribers in the system. 0 is unlimited'),
            'sync_custom_fields_values' => $this->t('Enable this if you need to populate all the custom fields with their default values if they are freshly created in a list and they have no value'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getUnsubscribeDays(): int
    {
        return (int)$this->unsubscribe_days;
    }

    /**
     * @return int
     */
    public function getUnconfirmDays(): int
    {
        return (int)$this->unconfirm_days;
    }

    /**
     * @return int
     */
    public function getBlacklistedDays(): int
    {
        return (int)$this->blacklisted_days;
    }

    /**
     * @return bool
     */
    public function getSyncCustomFieldsValues(): bool
    {
        return $this->sync_custom_fields_values === self::TEXT_YES;
    }
}
