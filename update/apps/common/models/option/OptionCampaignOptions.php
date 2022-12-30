<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCampaignOptions
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3
 */

class OptionCampaignOptions extends OptionBase
{

    /**
     * @var string
     */
    public $customer_select_delivery_servers = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.campaign.campaign_options';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['customer_select_delivery_servers', 'required'],
            ['customer_select_delivery_servers', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'customer_select_delivery_servers'   => $this->t('Customers can select delivery servers'),
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
            'customer_select_delivery_servers' => '',
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
            'customer_select_delivery_servers' => $this->t('Wheather the customers are able to select what delivery servers to use'),

        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
}
