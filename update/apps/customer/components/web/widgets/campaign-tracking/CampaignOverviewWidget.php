<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOverviewWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.3
 */

class CampaignOverviewWidget extends CWidget
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

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $webVersionUrl  = $optionUrl->getFrontendUrl();
        $webVersionUrl .= 'campaigns/' . $campaign->campaign_uid;
        $forwardsUrl    = 'javascript:;';
        $abusesUrl      = 'javascript:;';
        $recipientsUrl  = 'javascript:;';
        $shareReports   = null;

        if (apps()->isAppName('customer')) {
            $shareReports   = $campaign->shareReports;
            $forwardsUrl    = ['campaign_reports/forward_friend', 'campaign_uid' => $campaign->campaign_uid];
            $abusesUrl      = ['campaign_reports/abuse_reports', 'campaign_uid' => $campaign->campaign_uid];
            $recipientsUrl  = ['campaign_reports/delivery', 'campaign_uid' => $campaign->campaign_uid];
        } elseif (apps()->isAppName('frontend')) {
            $forwardsUrl    = ['campaigns_reports/forward_friend', 'campaign_uid' => $campaign->campaign_uid];
            $abusesUrl      = ['campaigns_reports/abuse_reports', 'campaign_uid' => $campaign->campaign_uid];
            $recipientsUrl  = ['campaigns_reports/delivery', 'campaign_uid' => $campaign->campaign_uid];
        }

        $recipientsLink = CHtml::link($campaign->getStats()->getProcessedCount(true), $recipientsUrl);
        $forwardsLink   = CHtml::link($campaign->countForwards(), $forwardsUrl);
        $abusesLink     = CHtml::link($campaign->countAbuseReports(), $abusesUrl);

        if (apps()->isAppName('backend')) {
            $recipientsLink = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->getStats()->getProcessedCount(true), sprintf('campaigns/%s/reports/delivery', $campaign->campaign_uid));
            $forwardsLink   = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->countForwards(), sprintf('campaigns/%s/reports/forward-friend', $campaign->campaign_uid));
            $abusesLink     = HtmlHelper::backendCreateCustomerResourceLink((int)$campaign->customer_id, (string)$campaign->countAbuseReports(), sprintf('campaigns/%s/reports/abuse-reports', $campaign->campaign_uid));
        }

        $recurringInfo = null;
        if ($campaign->getIsRecurring()) {
            $cron = new JQCron($campaign->getRecurringCronjob());
            $recurringInfo = $cron->getText(LanguageHelper::getAppLanguageCode());
        }

        $abTest = CampaignAbtest::model()->findByAttributes([
            'campaign_id'   => $campaign->campaign_id,
            'enabled'       => CampaignAbtest::TEXT_YES,
            'status'        => [CampaignAbtest::STATUS_ACTIVE, CampaignAbtest::STATUS_COMPLETE],
        ]);

        $abTestOpensUrl         = 'javascript:';
        $abTestClicksUrl        = 'javascript:';
        $abTestUnsubscribesUrl  = 'javascript:';
        $abTestComplainsUrl     = 'javascript:';
        $abTestBouncesUrl       = 'javascript:';
        if (!empty($abTest) && $abTest->getIsComplete() && isset($this->getController()->campaignReportsController)) {
            $completedAtDate = (string)date('Y-m-d H:i:s', (int)strtotime((string)$abTest->completed_at));
            $abTestOpensUrl  = createUrl($this->getController()->campaignReportsController . '/open_unique', [
                'campaign_uid'      => $campaign->campaign_uid,
                'CampaignTrackOpen' => ['date_added' => sprintf('>=%s', $completedAtDate)],
            ]);
            $abTestClicksUrl  = createUrl($this->getController()->campaignReportsController . '/click', [
                'campaign_uid'      => $campaign->campaign_uid,
                'click_start_date'  => $completedAtDate,
            ]);
            $abTestUnsubscribesUrl  = createUrl($this->getController()->campaignReportsController . '/unsubscribe', [
                'campaign_uid'             => $campaign->campaign_uid,
                'CampaignTrackUnsubscribe' => ['date_added' => sprintf('>=%s', $completedAtDate)],
            ]);
            $abTestComplainsUrl  = createUrl($this->getController()->campaignReportsController . '/complain', [
                'campaign_uid'          => $campaign->campaign_uid,
                'CampaignComplainLog'   => ['date_added' => sprintf('>=%s', $completedAtDate)],
            ]);
            $abTestBouncesUrl  = createUrl($this->getController()->campaignReportsController . '/bounce', [
                'campaign_uid'      => $campaign->campaign_uid,
                'CampaignBounceLog' => ['date_added' => sprintf('>=%s', $completedAtDate)],
            ]);
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/campaign-overview.js'));

        $this->render('overview', compact(
            'campaign',
            'webVersionUrl',
            'recurringInfo',
            'shareReports',
            'forwardsUrl',
            'abusesUrl',
            'recipientsUrl',
            'recipientsLink',
            'forwardsLink',
            'abusesLink',
            'abTest',
            'abTestOpensUrl',
            'abTestClicksUrl',
            'abTestBouncesUrl',
            'abTestComplainsUrl',
            'abTestUnsubscribesUrl'
        ));
    }
}
