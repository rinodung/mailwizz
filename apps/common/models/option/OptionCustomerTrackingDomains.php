<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerTrackingDomains
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.6
 */

class OptionCustomerTrackingDomains extends OptionBase
{
    /**
     * @var string
     */
    public $can_manage_tracking_domains = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_select_for_delivery_servers = self::TEXT_NO;

    /**
     * @var string
     */
    public $can_select_for_campaigns = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_tracking_domains';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['can_manage_tracking_domains, can_select_for_delivery_servers, can_select_for_campaigns', 'required'],
            ['can_manage_tracking_domains, can_select_for_delivery_servers, can_select_for_campaigns', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'can_manage_tracking_domains'     => $this->t('Can manage tracking domains'),
            'can_select_for_delivery_servers' => $this->t('Can select tracking domains for delivery servers'),
            'can_select_for_campaigns'        => $this->t('Can select tracking domains for campaigns'),
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
            'can_manage_tracking_domains'     => $this->t('Whether the customer is allowed to manage tracking domains. Please note that additional DNS settings must be done for this domain in order to allow the feature.'),
            'can_select_for_delivery_servers' => $this->t('Whether customers are allowed to select tracking domains for delivery servers'),
            'can_select_for_campaigns'        => $this->t('Whether customers are allowed to select tracking domains for campaigns'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }
}
