<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerQuotaCounters
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class OptionCustomerQuotaCounters extends OptionBase
{
    /**
     * @var string
     */
    public $campaign_emails = self::TEXT_YES;

    /**
     * @var string
     */
    public $campaign_test_emails = self::TEXT_YES;

    /**
     * @var string
     */
    public $template_test_emails = self::TEXT_YES;

    /**
     * @var string
     */
    public $list_emails = self::TEXT_YES;

    /**
     * @var string
     */
    public $transactional_emails = self::TEXT_YES;

    /**
     * @var string
     */
    public $campaign_giveup_emails = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.customer_quota_counters';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['campaign_emails, campaign_test_emails, template_test_emails, list_emails, transactional_emails, campaign_giveup_emails', 'required'],
            ['campaign_emails, campaign_test_emails, template_test_emails, list_emails, transactional_emails, campaign_giveup_emails', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'campaign_emails'               => $this->t('Count campaign emails'),
            'campaign_test_emails'          => $this->t('Count campaign test emails'),
            'template_test_emails'          => $this->t('Count template test emails'),
            'list_emails'                   => $this->t('Count list emails'),
            'transactional_emails'          => $this->t('Count transactional emails'),
            'campaign_giveup_emails'        => $this->t('Count campaign giveup emails'),
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
            'campaign_emails'               => '',
            'campaign_test_emails'          => '',
            'template_test_emails'          => '',
            'list_emails'                   => '',
            'transactional_emails'          => '',
            'campaign_giveup_emails'        => '',
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
            'campaign_emails'               => $this->t('Whether to count campaign emails against the customer sending quota'),
            'campaign_test_emails'          => $this->t('Whether to count campaign test emails against the customer sending quota'),
            'template_test_emails'          => $this->t('Whether to count template test emails against the customer sending quota'),
            'list_emails'                   => $this->t('Whether to count list emails against the customer sending quota'),
            'transactional_emails'          => $this->t('Whether to count transactional emails against the customer sending quota'),
            'campaign_giveup_emails'        => $this->t('Whether to count campaign giveup emails against the customer sending quota'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getQuotaPercentageList(): array
    {
        static $list = [];
        if (!empty($list)) {
            return $list;
        }

        for ($i = 1; $i <= 95; ++$i) {
            if ($i % 5 == 0) {
                $list[$i] = $i;
            }
        }

        return $list;
    }
}
