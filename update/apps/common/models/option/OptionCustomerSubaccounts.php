<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSubaccounts
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class OptionCustomerSubaccounts extends OptionBase
{
    /**
     * @var string
     */
    public $enabled = self::TEXT_NO;

    /**
     * @var int
     */
    public $max_subaccounts = -1;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_subaccounts';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['enabled, max_subaccounts', 'required'],
            ['enabled', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_subaccounts', 'numerical', 'integerOnly' => true, 'min' => -1, 'max' => 10000],
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
            'enabled'           => $this->t('Enabled'),
            'max_subaccounts'   => $this->t('Max. subaccounts'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = [];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'enabled'           => $this->t('Whether the feature is enabled'),
            'max_subaccounts'   => $this->t('Maximum number of subaccounts the customers can create, set to -1 for unlimited'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return (string)$this->enabled === self::TEXT_YES;
    }
}
