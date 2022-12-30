<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtBehaviorSettingsController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        return [
            'index'                              => [
                'keywords'          => ['common settings', 'pagination'],
                'keywordsGenerator' => [$this, '_indexKeywordsGenerator'],
            ],
            'cron'                               => [
                'keywords'          => ['speed'],
                'keywordsGenerator' => [$this, '_cronKeywordsGenerator'],
            ],
            'system_urls'                       => [
                'keywords'          => ['frontend urls', 'backend urls', 'customer urls', 'scheme', 'http', 'https', 'api urls'],
            ],
            'import_export'                      => [
                'keywords'          => ['import-export'],
                'keywordsGenerator' => [$this, '_importExportKeywordsGenerator'],
            ],
            'email_templates'                    => [
                'keywords'          => ['templating', 'common email templates', 'reinstall core templates'],
                'keywordsGenerator' => [$this, '_emailTemplatesKeywordsGenerator'],
            ],
            'email_blacklist'                    => [
                'keywords'          => ['abuse', 'spam'],
                'keywordsGenerator' => [$this, '_emailBlacklistKeywordsGenerator'],
            ],
            'api_ip_access'                      => [
                'keywords'          => ['api ip block', 'api ip access', 'deny ip', 'allow ip'],
                'keywordsGenerator' => [$this, '_apiIpAccessKeywordsGenerator'],
            ],
            'customer_common'                    => [
                'keywords'          => ['customers common settings'],
                'keywordsGenerator' => [$this, '_customerCommonKeywordsGenerator'],
            ],
            'customer_servers'                   => [
                'keywords'          => ['customers servers settings', 'bounce servers limits', 'feedback loop servers limits'],
                'keywordsGenerator' => [$this, '_customerServersKeywordsGenerator'],
            ],
            'customer_domains'                   => [
                'keywords'          => ['customers domains settings'],
                'keywordsGenerator' => [$this, '_customerDomainsKeywordsGenerator'],
            ],
            'customer_lists'                     => [
                'keywords'          => ['customers lists settings', 'settings segments', 'settings subscribers'],
                'keywordsGenerator' => [$this, '_customerListsKeywordsGenerator'],
            ],
            'customer_registration'              => [
                'keywords'          => ['customers registration settings'],
                'keywordsGenerator' => [$this, '_customerRegistrationKeywordsGenerator'],
            ],
            'customer_api'                       => [
                'keywords'          => ['customers api settings'],
                'keywordsGenerator' => [$this, '_customerApiKeywordsGenerator'],
            ],
            'customer_sending'                   => [
                'keywords'          => ['customers sending settings'],
                'keywordsGenerator' => [$this, '_customerSendingKeywordsGenerator'],
            ],
            'customer_quota_counters'            => [
                'keywords'          => ['customers quota counters settings'],
                'keywordsGenerator' => [$this, '_customerQuotaCountersKeywordsGenerator'],
            ],
            'customer_campaigns'                 => [
                'keywords'          => ['customer campaigns settings'],
                'keywordsGenerator' => [$this, '_customerCampaignsKeywordsGenerator'],
            ],
            'customer_cdn'                       => [
                'keywords'          => ['customer cdn settings'],
                'keywordsGenerator' => [$this, '_customerCdnKeywordsGenerator'],
            ],
            'campaign_attachments'               => [
                'keywords'          => ['campaigns attachments settings'],
                'keywordsGenerator' => [$this, '_campaignAttachmentsKeywordsGenerator'],
            ],
            'campaign_template_tags'             => [
                'keywords'          => ['campaigns template tags settings'],
                'keywordsGenerator' => [$this, '_campaignTemplateTagsKeywordsGenerator'],
            ],
            'campaign_exclude_ips_from_tracking' => [
                'keywords'          => ['campaigns exclude ip from tracking settings'],
                'keywordsGenerator' => [$this, '_campaignExcludeIpsFromTrackingKeywordsGenerator'],
            ],
            'campaign_blacklist_words'           => [
                'keywords'          => ['campaigns blacklist words settings'],
                'keywordsGenerator' => [$this, '_campaignBlacklistWordsKeywordsGenerator'],
            ],
            'campaign_template_engine'           => [
                'keywords'          => ['campaigns template engine settings'],
                'keywordsGenerator' => [$this, '_campaignTemplateEngineKeywordsGenerator'],
            ],
            'campaign_webhooks'                  => [
                'keywords'          => ['campaigns webhooks settings'],
                'keywordsGenerator' => [$this, '_campaignTemplateEngineKeywordsGenerator'],
            ],
            'campaign_misc'                      => [
                'keywords'          => ['campaigns miscellaneous settings'],
                'keywordsGenerator' => [$this, '_campaignMiscKeywordsGenerator'],
            ],
            'monetization'                       => [
                'keywords'          => ['monetization settings'],
                'keywordsGenerator' => [$this, '_monetizationKeywordsGenerator'],
            ],
            'monetization_orders'                => [
                'keywords'          => ['order monetization settings'],
                'keywordsGenerator' => [$this, '_monetizationOrdersKeywordsGenerator'],
            ],
            'monetization_invoices'              => [
                'keywords'          => ['invoices monetization settings'],
                'keywordsGenerator' => [$this, '_monetizationInvoicesKeywordsGenerator'],
            ],
            'license'                            => [
                'keywords'          => ['licensing'],
                'keywordsGenerator' => [$this, '_licenseKeywordsGenerator'],
            ],
            'social_links'                       => [
                'keywords'          => ['links'],
                'keywordsGenerator' => [$this, '_socialLinksKeywordsGenerator'],
            ],
            'cdn'                                => [
                'keywords'          => ['content delivery network'],
                'keywordsGenerator' => [$this, '_cdnKeywordsGenerator'],
            ],
            'spf_dkim'                           => [
                'keywords'          => ['sender policy framework', 'domainkeys identified mail'],
                'keywordsGenerator' => [$this, '_spfDkimKeywordsGenerator'],
            ],
            'customization'                      => [
                'keywords'          => ['background images'],
                'keywordsGenerator' => [$this, '_customizationKeywordsGenerator'],
            ],
            '2fa'                                => [
                'keywords'          => ['two factors authentication', '2 factors authentication'],
                'keywordsGenerator' => [$this, '_2faKeywordsGenerator'],
            ],
        ];
    }

    /**
     * @return array
     * @throws CException
     */
    public function _indexKeywordsGenerator(): array
    {
        /** @var OptionCommon $model */
        $model = container()->get(OptionCommon::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _cronKeywordsGenerator(): array
    {
        /** @var OptionCronDelivery $cronDeliveryModel */
        $cronDeliveryModel = container()->get(OptionCronDelivery::class);

        /** @var OptionCronProcessDeliveryBounce $cronLogsModel */
        $cronLogsModel = container()->get(OptionCronProcessDeliveryBounce::class);

        /** @var OptionCronProcessSubscribers $cronSubscribersModel */
        $cronSubscribersModel = container()->get(OptionCronProcessSubscribers::class);

        /** @var OptionCronProcessBounceServers $cronBouncesModel */
        $cronBouncesModel = container()->get(OptionCronProcessBounceServers::class);

        /** @var OptionCronProcessFeedbackLoopServers $cronFeedbackModel */
        $cronFeedbackModel = container()->get(OptionCronProcessFeedbackLoopServers::class);

        /** @var OptionCronProcessEmailBoxMonitors $cronEmailBoxModel */
        $cronEmailBoxModel = container()->get(OptionCronProcessEmailBoxMonitors::class);

        /** @var OptionCronProcessTransactionalEmails $cronTransEmailsModel */
        $cronTransEmailsModel = container()->get(OptionCronProcessTransactionalEmails::class);

        $keywords = [];
        $keywords = CMap::mergeArray($keywords, array_values($cronDeliveryModel->attributeLabels()));
        $keywords = CMap::mergeArray($keywords, array_values($cronLogsModel->attributeLabels()));
        $keywords = CMap::mergeArray($keywords, array_values($cronSubscribersModel->attributeLabels()));
        $keywords = CMap::mergeArray($keywords, array_values($cronBouncesModel->attributeLabels()));
        $keywords = CMap::mergeArray($keywords, array_values($cronFeedbackModel->attributeLabels()));
        $keywords = CMap::mergeArray($keywords, array_values($cronEmailBoxModel->attributeLabels()));

        return CMap::mergeArray($keywords, array_values($cronTransEmailsModel->attributeLabels()));
    }

    /**
     * @return array
     * @throws CException
     */
    public function _importExportKeywordsGenerator(): array
    {
        /** @var OptionImporter $importModel */
        $importModel = container()->get(OptionImporter::class);

        /** @var OptionExporter $exportModel */
        $exportModel = container()->get(OptionExporter::class);

        $keywords = [];
        $keywords = CMap::mergeArray($keywords, array_values($importModel->attributeLabels()));

        return CMap::mergeArray($keywords, array_values($exportModel->attributeLabels()));
    }

    /**
     * @return array
     * @throws CException
     */
    public function _emailTemplatesKeywordsGenerator(): array
    {
        /** @var OptionEmailTemplate $model */
        $model = container()->get(OptionEmailTemplate::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _emailBlacklistKeywordsGenerator(): array
    {
        /** @var OptionEmailBlacklist $model */
        $model = container()->get(OptionEmailBlacklist::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _apiIpAccessKeywordsGenerator(): array
    {
        /** @var OptionApiIpAccess $model */
        $model = container()->get(OptionApiIpAccess::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerCommonKeywordsGenerator(): array
    {
        /** @var OptionCustomerCommon $model */
        $model = container()->get(OptionCustomerCommon::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerServersKeywordsGenerator(): array
    {
        /** @var OptionCustomerServers $model */
        $model = container()->get(OptionCustomerServers::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerDomainsKeywordsGenerator(): array
    {
        /** @var OptionCustomerTrackingDomains $trackingModel */
        $trackingModel = container()->get(OptionCustomerTrackingDomains::class);

        /** @var OptionCustomerSendingDomains $sendingModel */
        $sendingModel  = container()->get(OptionCustomerSendingDomains::class);

        $keywords = [];
        $keywords = CMap::mergeArray($keywords, array_values($trackingModel->attributeLabels()));

        return CMap::mergeArray($keywords, array_values($sendingModel->attributeLabels()));
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerListsKeywordsGenerator(): array
    {
        /** @var OptionCustomerLists $model */
        $model = container()->get(OptionCustomerLists::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerRegistrationKeywordsGenerator(): array
    {
        /** @var OptionCustomerRegistration $model */
        $model = container()->get(OptionCustomerRegistration::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerApiKeywordsGenerator(): array
    {
        /** @var OptionCustomerApi $model */
        $model = container()->get(OptionCustomerApi::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerSendingKeywordsGenerator(): array
    {
        /** @var OptionCustomerSending $model */
        $model = container()->get(OptionCustomerSending::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerQuotaCountersKeywordsGenerator(): array
    {
        /** @var OptionCustomerQuotaCounters $model */
        $model = container()->get(OptionCustomerQuotaCounters::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerCampaignsKeywordsGenerator(): array
    {
        /** @var OptionCustomerCampaigns $model */
        $model = container()->get(OptionCustomerCampaigns::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customerCdnKeywordsGenerator(): array
    {
        /** @var OptionCustomerCdn $model */
        $model = container()->get(OptionCustomerCdn::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignAttachmentsKeywordsGenerator(): array
    {
        /** @var OptionCampaignAttachment $model */
        $model = container()->get(OptionCampaignAttachment::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignTemplateTagsKeywordsGenerator(): array
    {
        /** @var OptionCampaignTemplateTag $model */
        $model = container()->get(OptionCampaignTemplateTag::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignExcludeIpsFromTrackingKeywordsGenerator(): array
    {
        /** @var OptionCampaignExcludeIpsFromTracking $model */
        $model = container()->get(OptionCampaignExcludeIpsFromTracking::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignBlacklistWordsKeywordsGenerator(): array
    {
        /** @var OptionCampaignBlacklistWords $model */
        $model = container()->get(OptionCampaignBlacklistWords::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignTemplateEngineKeywordsGenerator(): array
    {
        /** @var OptionCampaignTemplateEngine $model */
        $model = container()->get(OptionCampaignTemplateEngine::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignWebhooksKeywordsGenerator(): array
    {
        /** @var OptionCampaignWebhooks $model */
        $model = container()->get(OptionCampaignWebhooks::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _campaignMiscKeywordsGenerator(): array
    {
        /** @var OptionCampaignMisc $model */
        $model = container()->get(OptionCampaignMisc::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _monetizationKeywordsGenerator(): array
    {
        /** @var OptionMonetizationMonetization $model */
        $model = container()->get(OptionMonetizationMonetization::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _monetizationOrdersKeywordsGenerator(): array
    {
        /** @var OptionMonetizationOrders $model */
        $model = container()->get(OptionMonetizationOrders::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _monetizationInvoicesKeywordsGenerator(): array
    {
        /** @var OptionMonetizationInvoices $model */
        $model = container()->get(OptionMonetizationInvoices::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _licenseKeywordsGenerator(): array
    {
        /** @var OptionLicense $model */
        $model = container()->get(OptionLicense::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _socialLinksKeywordsGenerator(): array
    {
        /** @var OptionSocialLinks $model */
        $model = container()->get(OptionSocialLinks::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _cdnKeywordsGenerator(): array
    {
        /** @var OptionCdn $model */
        $model = container()->get(OptionCdn::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _spfDkimKeywordsGenerator(): array
    {
        /** @var OptionSpfDkim $model */
        $model = container()->get(OptionSpfDkim::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _customizationKeywordsGenerator(): array
    {
        /** @var OptionCustomization $model */
        $model = container()->get(OptionCustomization::class);
        return array_values($model->attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function _2faKeywordsGenerator(): array
    {
        /** @var OptionTwoFactorAuth $model */
        $model = container()->get(OptionTwoFactorAuth::class);
        return array_values($model->attributeLabels());
    }
}
