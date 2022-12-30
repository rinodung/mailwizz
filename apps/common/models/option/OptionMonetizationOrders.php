<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionMonetizationOrders
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

class OptionMonetizationOrders extends OptionBase
{
    /**
     * @var int
     */
    public $uncomplete_days_removal = 7;

    /**
     * @var string
     */
    protected $_categoryName = 'system.monetization.orders';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['uncomplete_days_removal', 'required'],
            ['uncomplete_days_removal', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 365],
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
            'uncomplete_days_removal' => $this->t('Uncomplete orders removal days'),
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
            'uncomplete_days_removal' => '',
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
            'uncomplete_days_removal' => $this->t('How many days to keep the uncompleted orders in the system before permanent removal'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return int
     */
    public function getUncompleteDaysRemoval(): int
    {
        return (int)$this->uncomplete_days_removal;
    }
}
