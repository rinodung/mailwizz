<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSendingDomains
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

class OptionCustomerSendingDomains extends OptionBase
{
    /**
     * @var string
     */
    public $can_manage_sending_domains = self::TEXT_NO;

    /**
     * @var int
     */
    public $max_sending_domains = -1;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_sending_domains';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['can_manage_sending_domains, max_sending_domains', 'required'],
            ['can_manage_sending_domains', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_sending_domains', 'numerical', 'integerOnly' => true, 'min' => -1],
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
            'can_manage_sending_domains'  => $this->t('Can manage sending domains'),
            'max_sending_domains'         => $this->t('Max. sending domains'),
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
            'can_manage_sending_domains'   => $this->t('Whether the customer is allowed to add sending domains.'),
            'max_sending_domains'          => $this->t('Max number of sending domains a customer is allowed to add. Set to -1 for unlimited.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
}
