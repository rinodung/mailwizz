<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCronDeleteLogs
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.9
 */

class OptionCronDeleteLogs extends OptionBase
{
    /**
     * @var string
     */
    public $delete_campaign_delivery_logs = self::TEXT_NO;

    /**
     * @var string
     */
    public $delete_campaign_bounce_logs = self::TEXT_NO;

    /**
     * @var string
     */
    public $delete_campaign_open_logs = self::TEXT_NO;

    /**
     * @var string
     */
    public $delete_campaign_click_logs = self::TEXT_NO;

    /**
     * @var string
     */
    protected $_categoryName = 'system.cron.delete_logs';

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['delete_campaign_delivery_logs, delete_campaign_bounce_logs, delete_campaign_open_logs, delete_campaign_click_logs', 'required'],
            ['delete_campaign_delivery_logs, delete_campaign_bounce_logs, delete_campaign_open_logs, delete_campaign_click_logs', 'in', 'range' => array_keys($this->getYesNoOptions())],
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
            'delete_campaign_delivery_logs' => $this->t('Delete campaign delivery logs'),
            'delete_campaign_bounce_logs'   => $this->t('Delete campaign bounce logs'),
            'delete_campaign_open_logs'     => $this->t('Delete campaign open logs'),
            'delete_campaign_click_logs'    => $this->t('Delete campaign click logs'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'delete_campaign_delivery_logs' => $this->t('Whether to delete the campaign delivery logs after the campaign has been sent. If this is enabled, you will not be able to see the logs related to delivery but it will improve overall system performance. Keep in mind that we purge the logs after {n} days since the campaign finishes sending.', [
                '{n}' => app_param('campaign.delivery.logs.delete.days_back', 5),
            ]),

            'delete_campaign_bounce_logs' => $this->t('Whether to delete the campaign bounce logs after the campaign has been sent. If this is enabled, you will not be able to see the logs related to bounces but it will improve overall system performance. Keep in mind that we purge the logs after {n} days since the campaign finishes sending.', [
                '{n}' => app_param('campaign.bounce.logs.delete.days_back', 5),
            ]),

            'delete_campaign_open_logs' => $this->t('Whether to delete the campaign open logs after the campaign has been sent. If this is enabled, you will not be able to see the logs related to opens but it will improve overall system performance. Keep in mind that we purge the logs after {n} days since the campaign finishes sending.', [
                '{n}' => app_param('campaign.open.logs.delete.days_back', 5),
            ]),

            'delete_campaign_click_logs' => $this->t('Whether to delete the campaign click logs after the campaign has been sent. If this is enabled, you will not be able to see the logs related to clicks but it will improve overall system performance. Keep in mind that we purge the logs after {n} days since the campaign finishes sending.', [
                '{n}' => app_param('campaign.click.logs.delete.days_back', 5),
            ]),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    public function getDeleteCampaignDeliveryLogs(): bool
    {
        return $this->delete_campaign_delivery_logs === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getDeleteCampaignBounceLogs(): bool
    {
        return $this->delete_campaign_bounce_logs === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getDeleteCampaignOpenLogs(): bool
    {
        return $this->delete_campaign_open_logs === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function getDeleteCampaignClickLogs(): bool
    {
        return $this->delete_campaign_click_logs === self::TEXT_YES;
    }
}
