<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronProcessTransactionalEmails
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.9
 */

class OptionCronProcessTransactionalEmails extends OptionBase
{
    /**
     * @var int
     */
    public $delete_days_back = -1;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.transactional_emails';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['delete_days_back', 'required'],
            ['delete_days_back', 'numerical', 'min' => -1, 'max' => 3650],
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
            'delete_days_back' => $this->t('Delete days back'),
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
            'delete_days_back' => -1,
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
            'delete_days_back' => $this->t('Delete emails that are older than this amount of days. Increasing the number of days increases the amount of emails to be processed. -1 means never delete these.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getDeleteDaysBack(): int
    {
        return (int)$this->delete_days_back;
    }
}
