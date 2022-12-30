<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOverviewCounterBoxesWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.3
 */

class CampaignOverviewCounterBoxesWidget extends CWidget
{
    /**
     * @var Campaign
     */
    public $campaign;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $campaign = $this->campaign;

        if ($campaign->status == Campaign::STATUS_DRAFT) {
            return;
        }

        $canExportStats   = false;
        $opensUrl        = 'javascript:;';
        $clicksUrl       = 'javascript:;';
        $unsubscribesUrl = 'javascript:;';
        $complaintsUrl   = 'javascript:;';
        $bouncesUrl      = 'javascript:;';

        if (isset($this->getController()->campaignReportsController)) {
            $canExportStats     = ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes');
            $opensUrl           = createUrl($this->getController()->campaignReportsController . '/open_unique', ['campaign_uid' => $campaign->campaign_uid]);
            $clicksUrl          = createUrl($this->getController()->campaignReportsController . '/click', ['campaign_uid' => $campaign->campaign_uid]);
            $unsubscribesUrl    = createUrl($this->getController()->campaignReportsController . '/unsubscribe', ['campaign_uid' => $campaign->campaign_uid]);
            $complaintsUrl      = createUrl($this->getController()->campaignReportsController . '/complain', ['campaign_uid' => $campaign->campaign_uid]);
            $bouncesUrl         = createUrl($this->getController()->campaignReportsController . '/bounce', ['campaign_uid' => $campaign->campaign_uid]);
        }

        if (apps()->isAppName('backend')) {
            $opensLink          = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getUniqueOpensCount(true), sprintf('campaigns/%s/reports/open-unique', $campaign->campaign_uid));
            $clicksLink         = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getUniqueClicksCount(true), sprintf('campaigns/%s/reports/click', $campaign->campaign_uid));
            $unsubscribesLink   = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getUnsubscribesCount(true), sprintf('campaigns/%s/reports/unsubscribe', $campaign->campaign_uid));
            $complaintsLink     = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getComplaintsCount(true), sprintf('campaigns/%s/reports/complain', $campaign->campaign_uid));
            $bouncesLink        = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getBouncesCount(true), sprintf('campaigns/%s/reports/bounce', $campaign->campaign_uid));
        } else {
            $opensLink          = CHtml::link($campaign->getStats()->getUniqueOpensCount(true), $opensUrl);
            $clicksLink         = CHtml::link($campaign->getStats()->getUniqueClicksCount(true), $clicksUrl);
            $unsubscribesLink   = CHtml::link($campaign->getStats()->getUnsubscribesCount(true), $unsubscribesUrl);
            $complaintsLink     = CHtml::link($campaign->getStats()->getComplaintsCount(true), $complaintsUrl);
            $bouncesLink        = CHtml::link($campaign->getStats()->getBouncesCount(true), $bouncesUrl);
        }

        $this->render('overview-counter-boxes', compact(
            'campaign',
            'canExportStats',
            'opensLink',
            'clicksLink',
            'unsubscribesLink',
            'complaintsLink',
            'bouncesLink'
        ));
    }
}
