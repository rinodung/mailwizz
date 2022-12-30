<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignsController
 *
 * Handles the actions for campaigns related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CampaignsController extends Controller
{
    /**
     * @var string
     */
    public $campaignReportsController = 'campaign_reports';

    /**
     * @var string
     */
    public $campaignReportsExportController = 'campaign_reports_export';

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageStyle(['src' => AssetsUrl::js('datetimepicker/css/bootstrap-datetimepicker.min.css')]);
        $this->addPageScript(['src' => AssetsUrl::js('datetimepicker/js/bootstrap-datetimepicker.min.js')]);

        $languageCode = LanguageHelper::getAppLanguageCode();

        /** @var CWebApplication $app */
        $app = app();

        if ($app->getLanguage() != $app->sourceLanguage && is_file(AssetsPath::js($languageFile = 'datetimepicker/js/locales/bootstrap-datetimepicker.' . $languageCode . '.js'))) {
            $this->addPageScript(['src' => AssetsUrl::js($languageFile)]);
        }

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/js/jqcron/jqCron.css')]);
        $this->addPageScript(['src' => apps()->getBaseUrl('assets/js/jqcron/jqCron.js')]);
        if (is_file((string)Yii::getPathOfAlias('root.assets.js') . '/jqcron/jqCron.' . $languageCode . '.js')) {
            $this->addPageScript(['src' => apps()->getBaseUrl('assets/js/jqcron/jqCron.' . $languageCode . '.js')]);
            $this->setData('jqCronLanguage', $languageCode);
        } else {
            $this->addPageScript(['src' => apps()->getBaseUrl('assets/js/jqcron/jqCron.en.js')]);
            $this->setData('jqCronLanguage', 'en');
        }

        $this->addPageScript(['src' => AssetsUrl::js('campaigns.js')]);
        $this->addPageStyle(['src' => AssetsUrl::css('wizard.css')]);

        $this->onBeforeAction = [$this, '_registerJuiBs'];

        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + 
            delete, pause_unpause, copy, resume_sending, remove_attachment, 
            test, marksent, bulk_action, google_utm_tags, share_reports,
            share_reports_send_email, resend_giveups, import_from_share_code
            ',
        ], parent::filters());
    }

    /**
     * List available campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();
        $campaign->customer_id = (int)customer()->getId();

        // 1.6.0
        $this->setData([
            'lastTestEmails'    => session()->get('campaignLastTestEmails'),
            'lastTestFromEmail' => session()->get('campaignLastTestFrom'),
        ]);

        // 1.7.6
        $shareCode = new CampaignShareCodeImport();
        $shareCode->customer_id = (int)customer()->getId();
        $this->setData([
            'shareCode' => $shareCode,
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns'),
            'pageHeading'     => t('campaigns', 'Campaigns'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index', compact('campaign'));
    }

    /**
     * List available regular campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionRegular()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();
        $campaign->customer_id = (int)customer()->getId();
        $campaign->type        = Campaign::TYPE_REGULAR;

        // 1.6.0
        $this->setData([
            'lastTestEmails'    => session()->get('campaignLastTestEmails'),
            'lastTestFromEmail' => session()->get('campaignLastTestFrom'),
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns') . ' | ' . t('campaigns', 'Regular campaigns'),
            'pageHeading'     => t('campaigns', 'Regular campaigns'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Regular campaigns') => createUrl('campaigns/regular'),
                t('app', 'View all'),
            ],
        ]);

        $this->render($campaign->type, compact('campaign'));
    }

    /**
     * List available autoresponder campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionAutoresponder()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();
        $campaign->customer_id = (int)customer()->getId();
        $campaign->type        = Campaign::TYPE_AUTORESPONDER;
        $campaign->addRelatedRecord('option', new CampaignOption(), false);

        // 1.6.0
        $this->setData([
            'lastTestEmails'    => session()->get('campaignLastTestEmails'),
            'lastTestFromEmail' => session()->get('campaignLastTestFrom'),
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns') . ' | ' . t('campaigns', 'Autoresponders'),
            'pageHeading'     => t('campaigns', 'Autoresponders'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Autoresponders') => createUrl('campaigns/autoresponder'),
                t('app', 'View all'),
            ],
        ]);

        $this->render($campaign->type, compact('campaign'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOverview($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if (!$campaign->getAccessOverview()) {
            $this->redirect(['campaigns/setup', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaign overview'),
            'pageHeading'     => t('campaigns', 'Campaign overview'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/' . $campaign->type),
                $campaign->name . ' ' => createUrl('campaigns/overview', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Overview'),
            ],
        ]);

        // render
        $this->render('overview', compact('campaign'));
    }

    /**
     * Create a new campaign
     *
     * @param string $type
     *
     * @return void
     * @throws CException
     */
    public function actionCreate($type = '')
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $indexUrl = ['campaigns/index'];

        $campaign = new Campaign('step-name');
        $campaign->customer_id = (int)$customer->customer_id;

        $types = $campaign->getTypesList();
        if ($type && isset($types[$type])) {
            $campaign->type = $type;
            $indexUrl = ['campaigns/' . $campaign->type];
        }

        if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxCampaigns) {
                notify()->addWarning(t('lists', 'You have reached the maximum number of allowed campaigns.'));
                $this->redirect($indexUrl);
            }
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($campaign->getModelName(), []))) {
            $campaign->attributes = $attributes;
            if ($campaign->save()) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->campaignCreated($campaign);

                $option = new CampaignOption();
                $option->campaign_id = (int)$campaign->campaign_id;
                $option->save();

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'campaign'  => $campaign,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['campaigns/setup', 'campaign_uid' => $campaign->campaign_uid]);
            }
        }

        $listsArray      = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getListsDropDownArray());
        $segmentsArray   = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getSegmentsDropDownArray());
        $groupsArray     = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getGroupsDropDownArray());
        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Create new campaign'),
            'pageHeading'     => t('campaigns', 'Create new campaign'),
            'pageBreadcrumbs' =>  [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('step-name', compact('campaign', 'listsArray', 'segmentsArray', 'groupsArray', 'canSegmentLists'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!$campaign->getEditable()) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }
        $campaignRef = clone $campaign;
        $campaign->setScenario('step-name');

        if (request()->getIsPostRequest()) {

            // 1.3.8.8
            $attributes = (array)request()->getPost($campaign->getModelName(), []);

            // since 1.3.4.2 we don't allow changing the list/segment if the campaign is paused.
            if ($campaign->getIsPaused()) {

                // 1.6.0
                $attributes = [
                    'name'     => $attributes['name'] ?? '',
                    'group_id' => $attributes['group_id'] ?? null,
                ];
            }

            $campaign->attributes = $attributes;
            if ($campaign->save()) {

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->campaignUpdated($campaign);

                // since 1.3.6.2
                if ($campaignRef->list_id != $campaign->list_id) {
                    CampaignOpenActionSubscriber::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    CampaignOpenActionListField::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    CampaignSentActionListField::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    CampaignTemplateUrlActionListField::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                }
                //

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'campaign'  => $campaign,
            ]));

            if ($collection->itemAt('success')) {
                $redirect = ['campaigns/setup', 'campaign_uid' => $campaign->campaign_uid];

                // since 2.1.11 - Autosaves the form when navigating using the wizzard
                $wizzardRedirect = (string)request()->getPost('campaign_autosave_next_url', '');
                if (UrlHelper::belongsToApp($wizzardRedirect)) {
                    $redirect = $wizzardRedirect;
                }

                $this->redirect($redirect);
            }
        }

        $listsArray      = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getListsDropDownArray());
        $segmentsArray   = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getSegmentsDropDownArray());
        $groupsArray     = CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getGroupsDropDownArray());
        $canSegmentLists = $customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes';

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Update campaign'),
            'pageHeading'     => t('campaigns', 'Update campaign'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                $campaign->name . ' ' => createUrl('campaigns/update', ['campaign_uid' => $campaign_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('step-name', compact('campaign', 'listsArray', 'segmentsArray', 'groupsArray', 'canSegmentLists'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionSetup($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if (!$campaign->getEditable()) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $campaign->setScenario('step-setup');

        /** @var ListDefault|null $default */
        $default    = $campaign->list->default;
        $sameFields = ['from_name', 'from_email', 'subject', 'reply_to'];

        if (!empty($default)) {
            foreach ($sameFields as $attribute) {
                if (empty($campaign->$attribute)) {
                    $campaign->$attribute = $default->$attribute;
                }
            }
        }

        // customer reference
        $customer = $campaign->list->customer;

        // since 2.0.29 - AB Test
        $abTest         = null;
        $abTestExisted  = false;
        $abTestSubject  = null;
        $abTestSubjects = [];
        if ($campaign->getCanDoAbTest()) {
            $abTest = CampaignAbtest::model()->findByAttributes([
                'campaign_id' => $campaign->campaign_id,
            ]);
            $abTestExisted = !empty($abTest);
            if (empty($abTest)) {
                $abTest = new CampaignAbtest();
                $abTest->campaign_id = (int)$campaign->campaign_id;
                $abTest->status = CampaignAbtest::STATUS_ACTIVE;
            }
            $abTestSubject = new CampaignAbtestSubject();

            /** @var CampaignAbtestSubject[] $abTestSubjects */
            $abTestSubjects = [];
            if (!empty($abTest->test_id)) {
                $abTestSubjects = CampaignAbtestSubject::model()->findAllByAttributes([
                    'test_id'   => (int)$abTest->test_id,
                    'status'    => CampaignAbtestSubject::STATUS_ACTIVE,
                ]);
            }
        }
        //

        // tracking domains:
        $canSelectTrackingDomains = $customer->getGroupOption('tracking_domains.can_select_for_campaigns', 'no') == 'yes';

        // delivery servers for this campaign - start
        $deliveryServers                = [];
        $canSelectDeliveryServers       = $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes';
        $campaignDeliveryServersArray   = [];
        $campaignToDeliveryServers      = CampaignToDeliveryServer::model();
        if ($canSelectDeliveryServers) {
            $deliveryServers = $customer->getAvailableDeliveryServers();

            $campaignDeliveryServers = $campaignToDeliveryServers->findAllByAttributes([
                'campaign_id' => $campaign->campaign_id,
            ]);

            foreach ($campaignDeliveryServers as $srv) {
                $campaignDeliveryServersArray[] = (int)$srv->server_id;
            }
        }
        // delivery servers for this campaign - end

        // suppression lists for this campaign - start
        $suppressionListToCampaign = CustomerSuppressionListToCampaign::model();
        $canSelectSuppressionLists = $customer->getGroupOption('lists.can_use_own_blacklist', 'no') == 'yes';
        $selectedSuppressionLists  = [];
        $allSuppressionLists       = [];
        if ($canSelectSuppressionLists) {
            $allSuppressionLists = CustomerSuppressionList::model()->findAllByAttributes([
                'customer_id' => $campaign->customer_id,
            ]);
            $campaignSuppressionLists = $suppressionListToCampaign->findAllByAttributes([
                'campaign_id' => $campaign->campaign_id,
            ]);
            foreach ($campaignSuppressionLists as $suppressionList) {
                $selectedSuppressionLists[] = (int)$suppressionList->list_id;
            }
        }

        // suppression lists for this campaign - end

        /** @var OptionCampaignAttachment $optionCampaignAttachment */
        $optionCampaignAttachment = container()->get(OptionCampaignAttachment::class);

        // attachments - start
        $canAddAttachments = $optionCampaignAttachment->getIsEnabled();

        /** @var CampaignAttachment $attachment */
        $attachment = null;
        if ($canAddAttachments) {
            $attachment = new CampaignAttachment('multi-upload');
            $attachment->campaign_id = (int)$campaign->campaign_id;
        }
        // attachments - end

        // actions upon open - start
        $openAction = new CampaignOpenActionSubscriber();
        $openAction->campaign_id = (int)$campaign->campaign_id;
        $openAllowedActions = CMap::mergeArray(['' => t('app', 'Choose')], $openAction->getActions());
        $openActionLists    = $campaign->getListsDropDownArray();
        foreach ($openActionLists as $list_id => $name) {
            if ($list_id == $campaign->list_id) {
                unset($openActionLists[$list_id]);
                break;
            }
        }
        $canShowOpenActions = !empty($openActionLists);
        $openActionLists    = CMap::mergeArray(['' => t('app', 'Choose')], $openActionLists);
        $openActions        = CampaignOpenActionSubscriber::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // actions upon open - end

        // actions upon sent - start
        $sentAction = new CampaignSentActionSubscriber();
        $sentAction->campaign_id = (int)$campaign->campaign_id;
        $sentAllowedActions = CMap::mergeArray(['' => t('app', 'Choose')], $sentAction->getActions());
        $sentActionLists    = $campaign->getListsDropDownArray();
        foreach ($sentActionLists as $list_id => $name) {
            if ($list_id == $campaign->list_id) {
                unset($sentActionLists[$list_id]);
                break;
            }
        }
        $canShowSentActions = !empty($sentActionLists);
        $sentActionLists    = CMap::mergeArray(['' => t('app', 'Choose')], $sentActionLists);
        $sentActions        = CampaignSentActionSubscriber::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // actions upon sent - end

        /** @var OptionCampaignWebhooks $optionCampaignWebhooks */
        $optionCampaignWebhooks = container()->get(OptionCampaignWebhooks::class);

        // 1.6.8 - webhooks for opens - start
        $webhooksEnabled = $optionCampaignWebhooks->getIsEnabled();
        $opensWebhook    = new CampaignTrackOpenWebhook();
        $opensWebhooks   = CampaignTrackOpenWebhook::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // webhooks for opens - end

        // 1.5.3 - campaign extra tags - start
        $extraTag = new CampaignExtraTag();
        $extraTag->campaign_id = (int)$campaign->campaign_id;
        $extraTags = CampaignExtraTag::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // campaign extra tags - end

        // populate list custom field upon open - start
        $openListFieldAction = new CampaignOpenActionListField();
        $openListFieldAction->campaign_id = (int)$campaign->campaign_id;
        $openListFieldAction->list_id     = (int)$campaign->list_id;
        $openListFieldActionOptions       = $openListFieldAction->getCustomFieldsAsDropDownOptions();
        $canShowOpenListFieldActions      = !empty($openListFieldActionOptions);
        $openListFieldActionOptions       = CMap::mergeArray(['' => t('app', 'Choose')], $openListFieldActionOptions);
        $openListFieldActions = CampaignOpenActionListField::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // populate list custom field upon open - end

        // populate list custom field upon sent - start
        $sentListFieldAction = new CampaignSentActionListField();
        $sentListFieldAction->campaign_id = (int)$campaign->campaign_id;
        $sentListFieldAction->list_id     = (int)$campaign->list_id;
        $sentListFieldActionOptions       = $sentListFieldAction->getCustomFieldsAsDropDownOptions();
        $canShowSentListFieldActions      = !empty($sentListFieldActionOptions);
        $sentListFieldActionOptions       = CMap::mergeArray(['' => t('app', 'Choose')], $sentListFieldActionOptions);
        $sentListFieldActions = CampaignSentActionListField::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        // populate list custom field upon sent - end

        // 1.3.8.8
        $openUnopenFiltersSelected = [];
        $openUnopenFilters = CampaignFilterOpenUnopen::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        foreach ($openUnopenFilters as $_filter) {
            $openUnopenFiltersSelected[] = (int)$_filter->previous_campaign_id;
        }
        $openUnopenFilter = new CampaignFilterOpenUnopen();
        $openUnopenFilter->previous_campaign_id = $openUnopenFiltersSelected;
        $openUnopenFilter->action               = !empty($openUnopenFilters) ? $openUnopenFilters[0]->action : '';
        //

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($campaign->getModelName(), []))) {
            $campaign->attributes = $attributes;
            $campaign->option->attributes = (array)request()->getPost($campaign->option->getModelName(), []);
            $post = (array)request()->getOriginalPost($campaign->getModelName(), []);
            if (isset($post['subject'])) {
                $campaign->subject = html_decode(strip_tags((string)ioFilter()->purify(html_decode((string)$post['subject']))));
            }

            if ($campaign->save() && $campaign->option->save()) {
                // 1.3.8.8 - open/unopen filters
                CampaignFilterOpenUnopen::model()->deleteAllByAttributes(['campaign_id' => $campaign->campaign_id]);
                if ($postAttributes = (array)request()->getPost($openUnopenFilter->getModelName(), [])) {
                    $openUnopenFilter->attributes = $postAttributes;

                    if (!empty($postAttributes['previous_campaign_id']) && is_array($postAttributes['previous_campaign_id'])) {
                        foreach ($postAttributes['previous_campaign_id'] as $previous_campaign_id) {
                            $openUnopenFilterModel = new CampaignFilterOpenUnopen();
                            $openUnopenFilterModel->campaign_id = (int)$campaign->campaign_id;
                            $openUnopenFilterModel->action = (string)$postAttributes['action'];
                            $openUnopenFilterModel->previous_campaign_id = (int)$previous_campaign_id;
                            $openUnopenFilterModel->save();
                        }
                    }
                }
                //

                // actions upon open against subscriber
                CampaignOpenActionSubscriber::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($openAction->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $openAct = new CampaignOpenActionSubscriber();
                        $openAct->attributes = (array)$attributes;
                        $openAct->campaign_id = (int)$campaign->campaign_id;
                        $openAct->save();
                    }
                }

                // actions upon sent against subscriber
                CampaignSentActionSubscriber::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($sentAction->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $sentAct = new CampaignSentActionSubscriber();
                        $sentAct->attributes = (array)$attributes;
                        $sentAct->campaign_id = (int)$campaign->campaign_id;
                        $sentAct->save();
                    }
                }

                // 1.5.3 - campaign extra tags
                CampaignExtraTag::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($extraTag->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $_extraTag = new CampaignExtraTag();
                        $_extraTag->attributes  = (array)$attributes;
                        $_extraTag->campaign_id = (int)$campaign->campaign_id;
                        $_extraTag->save();
                    }
                }

                // action upon open against subscriber custom fields.
                CampaignOpenActionListField::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($openListFieldAction->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $openListFieldAct = new CampaignOpenActionListField();
                        $openListFieldAct->attributes  = (array)$attributes;
                        $openListFieldAct->campaign_id = (int)$campaign->campaign_id;
                        $openListFieldAct->list_id     = (int)$campaign->list_id;
                        $openListFieldAct->save();
                    }
                }

                // action upon sent against subscriber custom fields.
                CampaignSentActionListField::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($sentListFieldAction->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $sentListFieldAct = new CampaignSentActionListField();
                        $sentListFieldAct->attributes  = (array)$attributes;
                        $sentListFieldAct->campaign_id = (int)$campaign->campaign_id;
                        $sentListFieldAct->list_id     = (int)$campaign->list_id;
                        $sentListFieldAct->save();
                    }
                }

                // 1.6.8
                CampaignTrackOpenWebhook::model()->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($postAttributes = (array)request()->getPost($opensWebhook->getModelName(), [])) {
                    /** @var array $attributes */
                    foreach ($postAttributes as $attributes) {
                        $openWebhookModel = new CampaignTrackOpenWebhook();
                        $openWebhookModel->attributes  = (array)$attributes;
                        $openWebhookModel->campaign_id = (int)$campaign->campaign_id;
                        $openWebhookModel->save();
                    }
                }
                //

                $campaignToDeliveryServers->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($canSelectDeliveryServers && ($attributes = (array)request()->getPost($campaignToDeliveryServers->getModelName(), []))) {
                    foreach ($attributes as $serverId) {
                        $relation = new CampaignToDeliveryServer();
                        $relation->campaign_id = (int)$campaign->campaign_id;
                        $relation->server_id = (int)$serverId;
                        $relation->save();
                    }
                }

                $suppressionListToCampaign->deleteAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
                if ($canSelectSuppressionLists && ($attributes = (array)request()->getPost($suppressionListToCampaign->getModelName(), []))) {
                    foreach ($attributes as $listId) {
                        $relation = new CustomerSuppressionListToCampaign();
                        $relation->campaign_id = (int)$campaign->campaign_id;
                        $relation->list_id     = (int)$listId;
                        $relation->save();
                    }
                }

                // since 1.3.5.9
                $showSuccess = true;

                // since 2.0.29
                if (
                    $campaign->getCanDoAbTest() &&
                    ($attributes = (array)request()->getPost($abTest->getModelName(), [])) &&
                    !empty($abTestSubject)
                ) {

                    // make sure we assign these, in case of error below, we want them visible
                    $postAbTestSubjects = (array)request()->getPost($abTestSubject->getModelName(), []);

                    // assign the data from post against the existing one
                    /** @var CampaignAbtestSubject[] $newAbTestSubjects */
                    $newAbTestSubjects = [];

                    /** @var array $postAbTestSubject */
                    foreach ($postAbTestSubjects as $postAbTestSubject) {
                        $_abTestSubject = null;
                        if (!empty($postAbTestSubject['subject_id'])) {
                            $_abTestSubject = array_filter($abTestSubjects, function (CampaignAbtestSubject $subject) use ($postAbTestSubject): bool {
                                return (int)$subject->subject_id === (int)$postAbTestSubject['subject_id'];
                            });
                            $_abTestSubject = !empty($_abTestSubject) ? array_shift($_abTestSubject) : null;
                        }
                        if (empty($_abTestSubject)) {
                            $_abTestSubject = new CampaignAbtestSubject();
                        }
                        $_abTestSubject->attributes = $postAbTestSubject;
                        $newAbTestSubjects[] = $_abTestSubject;
                    }
                    $abTestSubjects = $newAbTestSubjects;
                    unset($newAbTestSubjects);

                    $abTest->attributes  = $attributes;
                    $mustSaveAbTest      = ($abTest->getIsEnabled() || !empty($abTestExisted));
                    $abTestGenericErrors = [];

                    if ($mustSaveAbTest && empty($abTestSubjects)) {
                        $mustSaveAbTest = false;
                        $abTestGenericErrors[] = t('campaigns', 'Please provide the A/B Test items');
                    }

                    if ($mustSaveAbTest) {
                        if (!$abTest->save()) {
                            $abTestGenericErrors[] = t('app', 'Your form has a few errors. Please fix them and try again!');
                        }

                        // we do this because we want to see subject errors even when the test itself has errors
                        foreach ($abTestSubjects as $_abTestSubject) {
                            if (!empty($abTest->test_id)) {
                                $_abTestSubject->test_id = (int)$abTest->test_id;
                                $result = $_abTestSubject->save();
                            } else {
                                $result = $_abTestSubject->validate();
                            }

                            if (!$result) {
                                $abTestGenericErrors[] = t('app', 'Your form has a few errors. Please fix them and try again!');
                            }
                        }

                        $ids = array_filter(array_unique(array_map(function (CampaignAbtestSubject $subject): int {
                            return (int)$subject->subject_id;
                        }, $abTestSubjects)));
                        if (!empty($ids)) {
                            $criteria = new CDbCriteria();
                            $criteria->compare('test_id', (int)$abTest->test_id);
                            $criteria->addNotInCondition('subject_id', $ids);
                            CampaignAbtestSubject::model()->updateAll([
                                'status' => CampaignAbtestSubject::STATUS_PENDING_DELETE,
                            ], $criteria);
                        }
                    }

                    if ($abTestGenericErrors) {
                        notify()->addError($abTestGenericErrors);
                        $showSuccess = false;
                        $hasError    = true;
                    }
                }
                //

                if ($canAddAttachments && $attachments = CUploadedFile::getInstances($attachment, 'file')) {
                    $attachment->file = $attachments;
                    $attachment->validateAndSave();

                    // 1.8.1
                    if ($attachment->hasErrors()) {
                        notify()->addWarning(t('campaigns', 'Some files failed to be attached, here is why: {message}', [
                            '{message}' => '<br />' . $attachment->shortErrors->getAllAsString(),
                        ]));
                        $showSuccess = false;
                        $hasError    = true;
                    }
                }

                /** @var OptionCampaignMisc $optionCampaignMisc */
                $optionCampaignMisc = container()->get(OptionCampaignMisc::class);

                // since 1.3.5.9
                $emailParts  = explode('@', $campaign->from_email);
                $emailDomain = strtolower((string)$emailParts[1]);

                $notAllowedFromDomains = $optionCampaignMisc->getNotAllowedFromDomains();
                if (!empty($notAllowedFromDomains) && in_array($emailDomain, $notAllowedFromDomains)) {
                    notify()->addWarning(t('campaigns', 'You are not allowed to use "{domain}" domain in your "From email" field!', [
                        '{domain}' => CHtml::tag('strong', [], $emailDomain),
                    ]));
                    $campaign->from_email = '';
                    $campaign->save(false);
                    $campaign->from_email = implode('@', $emailParts);
                    $showSuccess = false;
                    $hasError    = true;
                }
                //

                // since 1.6.3
                if (empty($hasError)) {
                    $notAllowedFromPatterns = $optionCampaignMisc->getNotAllowedFromPatterns();
                    if (!empty($notAllowedFromPatterns)) {
                        foreach ($notAllowedFromPatterns as $notAllowedFromPattern) {
                            if (!@preg_match($notAllowedFromPattern, $campaign->from_email)) {
                                continue;
                            }

                            notify()->addWarning(t('campaigns', 'You are not allowed to use "{email}" email in your "From email" field!', [
                                '{email}' => CHtml::tag('strong', [], $campaign->from_email),
                            ]));

                            $campaign->from_email = '';
                            $campaign->save(false);
                            $campaign->from_email = implode('@', $emailParts);
                            $showSuccess = false;
                            $hasError    = true;
                            break;
                        }
                    }
                }
                //

                // since 1.3.4.7 - whether must validate sending domain - start
                if (empty($hasError) && !SendingDomain::model()->getRequirementsErrors() && $customer->getGroupOption('campaigns.must_verify_sending_domain', 'no') == 'yes') {
                    $sendingDomain = SendingDomain::model()->findVerifiedByEmail($campaign->from_email, (int)$campaign->customer_id);
                    if (empty($sendingDomain)) {
                        notify()->addWarning(t('campaigns', 'You are required to verify your sending domain({domain}) in order to be able to send this campaign!', [
                            '{domain}' => CHtml::tag('strong', [], $emailDomain),
                        ]));
                        notify()->addWarning(t('campaigns', 'Please click {link} to add and verify {domain} domain name. After verification, you can send your campaign.', [
                            '{link}'   => CHtml::link(t('app', 'here'), ['sending_domains/create']),
                            '{domain}' => CHtml::tag('strong', [], $emailDomain),
                        ]));
                    }
                }
                // whether must validate sending domain - end

                if ($showSuccess) {
                    notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                }
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'campaign'  => $campaign,
            ]));

            if ($collection->itemAt('success')) {
                $redirect = ['campaigns/template', 'campaign_uid' => $campaign->campaign_uid];

                // since 2.1.11 - Autosaves the form when navigating using the wizzard
                $wizzardRedirect = (string)request()->getPost('campaign_autosave_next_url', '');
                if (UrlHelper::belongsToApp($wizzardRedirect)) {
                    $redirect = $wizzardRedirect;
                }

                $this->redirect($redirect);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Setup campaign'),
            'pageHeading'     => t('campaigns', 'Campaign setup'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                $campaign->name . ' ' => createUrl('campaigns/update', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Setup'),
            ],
        ]);

        $this->render('step-setup', compact(
            'campaign',
            'abTest',
            'abTestSubject',
            'abTestSubjects',
            'canSelectDeliveryServers',
            'campaignToDeliveryServers',
            'deliveryServers',
            'campaignDeliveryServersArray',
            'canAddAttachments',
            'attachment',
            'canShowOpenActions',
            'openAction',
            'openActions',
            'openAllowedActions',
            'openActionLists',
            'canShowSentActions',
            'sentAction',
            'sentActions',
            'sentAllowedActions',
            'sentActionLists',
            'webhooksEnabled',
            'opensWebhook',
            'opensWebhooks',
            'openListFieldAction',
            'openListFieldActions',
            'openListFieldActionOptions',
            'sentListFieldAction',
            'sentListFieldActions',
            'sentListFieldActionOptions',
            'canShowOpenListFieldActions',
            'canShowSentListFieldActions',
            'canSelectTrackingDomains',
            'openUnopenFilter',
            'suppressionListToCampaign',
            'canSelectSuppressionLists',
            'selectedSuppressionLists',
            'allSuppressionLists',
            'extraTag',
            'extraTags'
        ));
    }

    /**
     * @param string $campaign_uid
     * @param string $do
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionTemplate($campaign_uid, $do = 'create')
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if (!$campaign->getEditable()) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        /** @var string $viewFile */
        $viewFile = '';

        if ($do === 'select') {
            if ($template_uid = (string)request()->getQuery('template_uid', '')) {
                /** @var CampaignTemplate|null $campaignTemplate */
                $campaignTemplate = $campaign->template;
                if (empty($campaignTemplate)) {
                    $campaignTemplate = new CampaignTemplate();
                }
                $campaignTemplate->setScenario('copy');

                if (!empty($campaignTemplate->template_id)) {
                    CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes([
                        'template_id' => $campaignTemplate->template_id,
                    ]);
                }

                $selectedTemplate = CustomerEmailTemplate::model()->findByAttributes([
                    'template_uid'  => $template_uid,
                    'customer_id'   => (int)customer()->getId(),
                ]);

                $redirect = ['campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create'];

                if (!empty($selectedTemplate)) {

                    // 1.4.4
                    foreach ($selectedTemplate->attributes as $key => $value) {
                        if (in_array($key, ['template_id'])) {
                            continue;
                        }
                        if ($campaignTemplate->hasAttribute($key)) {
                            $campaignTemplate->$key = $value;
                        }
                    }
                    //

                    $campaignTemplate->campaign_id           = (int)$campaign->campaign_id;
                    $campaignTemplate->customer_template_id  = (int)$selectedTemplate->template_id;
                    $campaignTemplate->name                  = $selectedTemplate->name;
                    $campaignTemplate->content               = $selectedTemplate->content;

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $campaignTemplate->auto_plain_text === CampaignTemplate::TEXT_YES) {
                        $campaignTemplate->plain_text = CampaignHelper::htmlToText($selectedTemplate->content);
                    }

                    $campaignTemplate->save();

                    /**
                     * We also need to create a copy of the template files.
                     * This avoids the scenario where a campaign based on a uploaded template is sent
                     * then after a while the template is deleted.
                     * In this scenario, the campaign will remain without images.
                     */
                    $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');

                    // make sure the new template has images, otherwise don't bother.
                    $filesPath = $storagePath . '/' . $selectedTemplate->template_uid;
                    if (!file_exists($filesPath) || !is_dir($filesPath)) {
                        $this->redirect($redirect);
                    }

                    // check if there's already a copy if this campaign template. if so, remove it, we don't want a folder with 1000 images.
                    $campaignFiles = $storagePath . '/cmp' . $campaign->campaign_uid;
                    if (file_exists($campaignFiles) && is_dir($campaignFiles)) {
                        FileSystemHelper::deleteDirectoryContents($campaignFiles, true, 1);
                    }

                    // copy the template folder to the campaign folder.
                    if (!FileSystemHelper::copyOnlyDirectoryContents($filesPath, $campaignFiles)) {
                        $this->redirect($redirect);
                    }

                    $search = [
                        'frontend/assets/gallery/cmp' . $campaign->campaign_uid,
                        'frontend/assets/gallery/' . $selectedTemplate->template_uid,
                    ];
                    $replace = 'frontend/assets/gallery/cmp' . $campaign->campaign_uid;
                    $campaignTemplate->content = str_ireplace($search, $replace, $campaignTemplate->content);

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $campaignTemplate->auto_plain_text === CampaignTemplate::TEXT_YES) {
                        $campaignTemplate->plain_text = CampaignHelper::htmlToText($campaignTemplate->content);
                    }

                    $campaignTemplate->save(false);
                }
                $this->redirect($redirect);
            }

            $template = new CustomerEmailTemplate('search');
            $template->unsetAttributes();

            // for filters.
            $template->attributes  = (array)request()->getQuery($template->getModelName(), []);
            $template->customer_id = (int)customer()->getId();

            // pass to view
            $this->setData('template', $template);

            $viewFile = 'step-template-select';
        } elseif ($do === 'create' || $do === 'from-url') {
            /** @var CampaignTemplate|null $template */
            $template = $campaign->template;
            if (empty($template)) {
                $template = new CampaignTemplate();
            }
            $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];
            $template->campaign_id = (int)$campaign->campaign_id;
            $this->setData('template', $template);

            // 1.3.9.5
            $randomContent = new CampaignRandomContent();
            $randomContent->campaign_id = (int)$campaign->campaign_id;
            $randomContent->fieldDecorator->onHtmlOptionsSetup = [$this, '_setRandomContentEditorOptions'];
            $this->setData('randomContent', $randomContent);

            if (request()->getQuery('prev') == 'upload' && !empty($template->template_id)) {
                CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes([
                    'template_id' => $template->template_id,
                ]);
                CampaignTemplateUrlActionListField::model()->deleteAllByAttributes([
                    'template_id' => $template->template_id,
                ]);
                $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create']);
            }

            if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($template->getModelName(), []))) {
                /** @var array $post */
                $post = (array)request()->getOriginalPost('', []);

                $template->attributes = $attributes;
                if (isset($post[$template->getModelName()]['content'])) {
                    $template->content = (string)$post[$template->getModelName()]['content'];
                } else {
                    $template->content = '';
                }

                if ($campaign->option->plain_text_email != CampaignOption::TEXT_YES) {
                    $template->only_plain_text = CampaignTemplate::TEXT_NO;
                    $template->auto_plain_text = CampaignTemplate::TEXT_NO;
                    $template->plain_text      = '';
                }

                $template->campaign_id = (int)$campaign->campaign_id;

                // since 1.3.4.2, allow content fetched from url
                // TO DO: Add an option in backend to enable/disable this feature!
                $errors = [];
                if ($do === 'from-url' && isset($attributes['from_url'])) {
                    if (!FilterVarHelper::url((string)$attributes['from_url'])) {
                        $errors[] = t('campaigns', 'The provided url does not seem to be valid!');
                    } else {
                        $responseError = '';
                        $response      = '';
                        try {
                            $response = (string)(new GuzzleHttp\Client())->get((string)$attributes['from_url'])->getBody();
                        } catch (Exception $e) {
                            $responseError = $e->getMessage();
                        }
                        if ($responseError) {
                            $errors[] = $responseError;
                        } else {
                            // do a blind search after some common html elements
                            $elements = ['<div', '<table', '<a', '<p', '<br', 'style='];
                            $found = false;
                            foreach ($elements as $elem) {
                                if (stripos($response, $elem) !== false) {
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $errors[] = t('campaigns', 'The provided url does not seem to contain valid html!');
                            } else {
                                $template->content = $response;
                            }
                        }
                    }
                }

                if ($template->getIsOnlyPlainText()) {
                    $template->content    = html_decode((string)ioFilter()->purify($template->plain_text));
                    $template->plain_text = $template->content;
                }

                $isNext = request()->getPost('is_next', 0);

                if (!empty($template->content) && !empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $template->auto_plain_text === CampaignTemplate::TEXT_YES) {
                    $template->plain_text = CampaignHelper::htmlToText($template->content);
                }

                if (empty($errors) && $template->save()) {
                    notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                    $redirect = ['campaigns/template', 'campaign_uid' => $campaign_uid];
                    if ($isNext) {
                        $redirect = ['campaigns/confirm', 'campaign_uid' => $campaign_uid];
                    }

                    // since 1.3.4.3
                    CampaignTemplateUrlActionSubscriber::model()->deleteAllByAttributes([
                        'template_id' => $template->template_id,
                    ]);
                    if ($postAttributes = (array)request()->getPost('CampaignTemplateUrlActionSubscriber', [])) {
                        /** @var array $attributes */
                        foreach ($postAttributes as $attributes) {
                            $templateUrlActionSubscriber = new CampaignTemplateUrlActionSubscriber();
                            $templateUrlActionSubscriber->attributes  = (array)$attributes;
                            $templateUrlActionSubscriber->url         = StringHelper::normalizeUrl($templateUrlActionSubscriber->url);
                            $templateUrlActionSubscriber->template_id = (int)$template->template_id;
                            $templateUrlActionSubscriber->campaign_id = (int)$campaign->campaign_id;
                            $templateUrlActionSubscriber->save();
                        }
                    }

                    // since 1.6.8
                    CampaignTrackUrlWebhook::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    if ($postAttributes = (array)request()->getPost('CampaignTrackUrlWebhook', [])) {
                        /** @var array $attributes */
                        foreach ($postAttributes as $attributes) {
                            $urlWebhookModel = new CampaignTrackUrlWebhook();
                            $urlWebhookModel->attributes  = (array)$attributes;
                            $urlWebhookModel->track_url   = StringHelper::normalizeUrl($urlWebhookModel->track_url);
                            $urlWebhookModel->campaign_id = (int)$campaign->campaign_id;
                            $urlWebhookModel->save();
                        }
                    }

                    // since 1.3.4.5
                    CampaignTemplateUrlActionListField::model()->deleteAllByAttributes([
                        'template_id' => $template->template_id,
                    ]);
                    if ($postAttributes = (array)request()->getPost('CampaignTemplateUrlActionListField', [])) {
                        /** @var array $attributes */
                        foreach ($postAttributes as $attributes) {
                            $templateUrlActionListField = new CampaignTemplateUrlActionListField();
                            $templateUrlActionListField->attributes  = (array)$attributes;
                            $templateUrlActionListField->template_id = (int)$template->template_id;
                            $templateUrlActionListField->campaign_id = (int)$campaign->campaign_id;
                            $templateUrlActionListField->list_id     = (int)$campaign->list_id;
                            $templateUrlActionListField->save();
                        }
                    }

                    // since 1.3.9.5
                    CampaignRandomContent::model()->deleteAllByAttributes([
                        'campaign_id' => $campaign->campaign_id,
                    ]);
                    if ($postAttributes = (array)request()->getPost('CampaignRandomContent', [])) {
                        /** @var array $post */
                        $post = (array)request()->getOriginalPost('', []);

                        /**
                         * @var int $index
                         * @var array $attributes
                         */
                        foreach ($postAttributes as $index => $attributes) {
                            $rndContent = new CampaignRandomContent();
                            $rndContent->attributes  = (array)$attributes;
                            $rndContent->campaign_id = (int)$campaign->campaign_id;
                            $rndContent->content     = (string)$post['CampaignRandomContent'][$index]['content'];
                            try {
                                $rndContent->save();
                            } catch (Exception $e) {
                            }
                        }
                    }
                } else {
                    notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                    if (!empty($errors)) {
                        notify()->addError($errors);
                    }
                }

                hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                    'controller'=> $this,
                    'success'   => notify()->getHasSuccess(),
                    'do'        => $do,
                    'campaign'  => $campaign,
                    'template'  => $template,
                ]));

                if ($collection->itemAt('success')) {
                    // since 2.1.11 - Autosaves the form when navigating using the wizzard
                    $wizzardRedirect = (string)request()->getPost('campaign_autosave_next_url', '');
                    if (UrlHelper::belongsToApp($wizzardRedirect)) {
                        $redirect = $wizzardRedirect;
                    }

                    if (!empty($redirect)) {
                        $this->redirect($redirect);
                    }
                }
            }

            // since 1.3.4.3
            if ($campaign->option->url_tracking === CampaignOption::TEXT_YES && !empty($template->content)) {
                $contentUrls = $template->getContentUrls();
                if (!empty($contentUrls)) {
                    $templateListsArray = $campaign->getListsDropDownArray();
                    foreach ($templateListsArray as $list_id => $name) {
                        if ($list_id == $campaign->list_id) {
                            unset($templateListsArray[$list_id]);
                            break;
                        }
                    }

                    $templateUrlActionSubscriber = new CampaignTemplateUrlActionSubscriber();
                    $templateUrlActionSubscriber->campaign_id = (int)$campaign->campaign_id;

                    /** @var OptionCampaignWebhooks $optionCampaignWebhooks */
                    $optionCampaignWebhooks = container()->get(OptionCampaignWebhooks::class);

                    // 1.6.8 - webhooks for opens - start
                    $webhooksEnabled = $optionCampaignWebhooks->getIsEnabled();
                    $urlWebhook      = new CampaignTrackUrlWebhook();
                    $urlWebhook->campaign_id = (int)$campaign->campaign_id;

                    $this->setData([
                        'templateListsArray'                => !empty($templateListsArray) ? CMap::mergeArray(['' => t('app', 'Choose')], $templateListsArray) : [],
                        'templateContentUrls'               => CMap::mergeArray(['' => t('app', 'Choose')], array_combine($contentUrls, $contentUrls)),
                        'clickAllowedActions'               => CMap::mergeArray(['' => t('app', 'Choose')], $templateUrlActionSubscriber->getActions()),
                        'templateUrlActionSubscriber'       => $templateUrlActionSubscriber,
                        'templateUrlActionSubscriberModels' => $templateUrlActionSubscriber->findAllByAttributes(['template_id' => $template->template_id]),
                        'webhooksEnabled'                   => $webhooksEnabled,
                        'urlWebhook'                        => $urlWebhook,
                        'urlWebhookModels'                  => $urlWebhook->findAllByAttributes(['campaign_id' => $campaign->campaign_id]),
                    ]);

                    // since 1.3.4.5
                    $templateUrlActionListField = new CampaignTemplateUrlActionListField();
                    $templateUrlActionListField->campaign_id = (int)$campaign->campaign_id;
                    $templateUrlActionListField->list_id     = (int)$campaign->list_id;
                    $this->setData([
                        'templateUrlActionListField'  => $templateUrlActionListField,
                        'templateUrlActionListFields' => $templateUrlActionListField->findAllByAttributes(['template_id' => $template->template_id]),
                    ]);
                }
            }

            $this->setData('templateUp', new CampaignEmailTemplateUpload('upload'));
            $viewFile = 'step-template-create';
        } elseif ($do == 'upload') {
            if (request()->getIsPostRequest() && request()->getPost('is_next', 0)) {
                $this->redirect(['campaigns/confirm', 'campaign_uid' => $campaign_uid]);
            }

            // 1.3.9.5
            $randomContent = new CampaignRandomContent();
            $randomContent->campaign_id = (int)$campaign->campaign_id;
            $randomContent->fieldDecorator->onHtmlOptionsSetup = [$this, '_setRandomContentEditorOptions'];
            $this->setData('randomContent', $randomContent);

            /** @var CampaignTemplate|null $template */
            $template = $campaign->template;
            if (empty($template)) {
                $template = new CampaignTemplate();
            }
            $template->fieldDecorator->onHtmlOptionsSetup = [$this, '_setEditorOptions'];
            $template->campaign_id = (int)$campaign->campaign_id;

            $templateUp = new CampaignEmailTemplateUpload('upload');
            $templateUp->customer_id = (int)customer()->getId();
            $templateUp->campaign    = $campaign;

            $redirect = ['campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create', 'prev' => 'upload'];

            if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($templateUp->getModelName(), []))) {
                $templateUp->attributes = $attributes;
                $templateUp->archive = CUploadedFile::getInstance($templateUp, 'archive');
                if (!$templateUp->validate() || !$templateUp->uploader->handleUpload()) {
                    notify()->addError($templateUp->shortErrors->getAllAsString());
                } else {
                    $template->content = $templateUp->content;
                    $template->name    = basename($templateUp->archive->name, '.zip');

                    if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES && $templateUp->auto_plain_text === CampaignTemplate::TEXT_YES && empty($templateUp->plain_text)) {
                        $template->plain_text = CampaignHelper::htmlToText($templateUp->content);
                    }

                    if ($template->save()) {
                        notify()->addSuccess(t('app', 'Your file has been successfully uploaded!'));
                    } else {
                        notify()->addError($template->shortErrors->getAllAsString());
                    }
                }

                hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                    'controller'=> $this,
                    'success'   => notify()->getHasSuccess(),
                    'do'        => $do,
                    'campaign'  => $campaign,
                    'template'  => $template,
                    'templateUp'=> $templateUp,
                ]));

                if ($collection->itemAt('success')) {
                    $this->redirect($redirect);
                }
            }

            $this->setData([
                'templateUp' => $templateUp,
                'template'   => $template,
            ]);

            $viewFile = 'step-template-create';
        } else {
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid, 'do' => 'create']);
        }

        // since 1.3.4.2, add a warning if the campaign is paused and template changed
        if ($campaign->getIsPaused()) {
            notify()->addWarning(t('campaigns', 'This campaign is paused, please have this in mind if you are going to change the template, it will affect subscribers that already received the current template!'));
        }

        // 1.3.7.3
        $this->setData([
            'lastTestEmails'    => session()->get('campaignLastTestEmails'),
            'lastTestFromEmail' => session()->get('campaignLastTestFrom'),
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaign template'),
            'pageHeading'     => t('campaigns', 'Campaign template'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                $campaign->name . ' ' => createUrl('campaigns/update', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Template'),
            ],
        ]);

        $this->render($viewFile, compact('campaign'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionConfirm($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if (!$campaign->getEditable()) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();
        $campaign->setScenario('step-confirm');

        if ($campaign->getIsAutoresponder()) {
            $campaign->option->setScenario('step-confirm-ar');
        }

        $hasError = false;

        // added in 2.0.19
        $isNext = request()->getPost('is_next', 0);

        if (empty($campaign->template->content)) {
            $hasError = true;
            notify()->addError(t('campaigns', 'Missing campaign template!'));
        }

        // since 1.3.4.7 - must validate sending domain - start
        if (!SendingDomain::model()->getRequirementsErrors() && $customer->getGroupOption('campaigns.must_verify_sending_domain', 'no') == 'yes') {
            $sendingDomain = SendingDomain::model()->findVerifiedByEmail($campaign->from_email, (int)$campaign->customer_id);
            if (empty($sendingDomain)) {
                $emailParts = explode('@', $campaign->from_email);
                $domain = $emailParts[1];
                notify()->addError(t('campaigns', 'You are required to verify your sending domain({domain}) in order to be able to send this campaign!', [
                    '{domain}' => CHtml::tag('strong', [], $domain),
                ]));
                notify()->addError(t('campaigns', 'Please click {link} to add and verify {domain} domain name. After verification, you can send your campaign.', [
                    '{link}'   => CHtml::link(t('app', 'here'), ['sending_domains/create']),
                    '{domain}' => CHtml::tag('strong', [], $domain),
                ]));
                $hasError = true;
            }
        }
        // must validate sending domain - end

        // since 2.0.30
        if (($maxActiveCampaigns = (int)$customer->getGroupOption('campaigns.max_active_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addInCondition('status', [
                Campaign::STATUS_PENDING_SENDING,
                Campaign::STATUS_PENDING_APPROVE,
                Campaign::STATUS_PAUSED,
                Campaign::STATUS_SENDING,
                Campaign::STATUS_PROCESSING,
            ]);
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxActiveCampaigns) {
                notify()->addError(t('campaigns', 'You have reached the maximum number of allowed active campaigns.'));
                $hasError = true;
            }
        }
        //

        if (!$hasError && request()->getIsPostRequest()) {
            $campaign->attributes = (array)request()->getPost($campaign->getModelName(), []);

            if ($isNext) {
                $campaign->status = Campaign::STATUS_PENDING_SENDING;

                // since 1.3.4.2, we allow paused campaigns to be edited.
                if ($campaign->getIsPaused()) {
                    $campaign->status = Campaign::STATUS_PAUSED;
                }

                // 1.4.5
                $requireApproval = $customer->getGroupOption('campaigns.require_approval', 'no') == 'yes';
                if ($requireApproval) {
                    $campaign->markPendingApprove();
                }
            }

            $transaction = db()->beginTransaction();
            $redirect    = ['campaigns/' . $campaign->type];
            $saved       = false;

            if ($campaign->save()) {
                $saved = true;
                if ($campaign->getIsAutoresponder() || $campaign->getIsRegular()) {
                    $campaign->option->attributes = (array)request()->getPost($campaign->option->getModelName(), []);
                    if (!$campaign->option->save()) {
                        $saved = false;
                        notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                    }
                }

                if ($saved && $isNext) {

                    /** @var CustomerActionLogBehavior $logAction */
                    $logAction = $customer->getLogAction();
                    $logAction->campaignScheduled($campaign);

                    // since 1.3.5.9
                    $hasAddedSuccessMessage = false;
                    if (($sbw = $campaign->getSubjectBlacklistWords()) || ($cbw = $campaign->getContentBlacklistWords())) {
                        $hasAddedSuccessMessage = true;
                        $reason = [];
                        if (!empty($sbw)) {
                            $reason[] = 'Contains blacklisted words in campaign subject!';
                        }
                        if (!empty($cbw)) {
                            $reason[] = 'Contains blacklisted words in campaign body!';
                        }
                        $campaign->block(implode('<br />', $reason));

                        notify()->addSuccess(t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved but it will be blocked from sending until it is reviewed by one of our administrators!', [
                            '{campaignName}' => $campaign->name,
                            '{type}'         => t('campaigns', $campaign->type),
                        ]));
                    }
                    //

                    if (!$hasAddedSuccessMessage) {
                        $message = t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved and will start sending at {sendDateTime}!', [
                            '{campaignName}'    => $campaign->name,
                            '{sendDateTime}'    => $campaign->getSendAt(),
                            '{type}'            => t('campaigns', $campaign->type),
                        ]);
                        if ($requireApproval) {
                            $message = t('campaigns', 'Your campaign({type}) named "{campaignName}" has been successfully saved and will start sending after it will be approved, no early than {sendDateTime}!', [
                                '{campaignName}'    => $campaign->name,
                                '{sendDateTime}'    => $campaign->getSendAt(),
                                '{type}'            => t('campaigns', $campaign->type),
                            ]);
                        }
                        notify()->addSuccess($message);
                    }
                }

                if ($saved && !$isNext) {
                    notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
                    $redirect = null;
                }
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }

            if ($saved) {
                $transaction->commit();
            } else {
                $transaction->rollback();
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'campaign'  => $campaign,
            ]));

            if ($collection->itemAt('success')) {
                // since 2.1.11 - Autosaves the form when navigating using the wizzard
                $wizzardRedirect = (string)request()->getPost('campaign_autosave_next_url', '');
                if (UrlHelper::belongsToApp($wizzardRedirect)) {
                    $redirect = $wizzardRedirect;
                }

                if (!empty($redirect)) {
                    $this->redirect($redirect);
                }
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaign overview'),
            'pageHeading'     => t('campaigns', 'Campaign confirmation'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                html_encode($campaign->name) . ' ' => createUrl('campaigns/update', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Confirmation'),
            ],
        ]);

        $this->render('step-confirm', compact('campaign'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionTest($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $template = $campaign->template;

        if ($campaign->getIsPendingDelete()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        if (!$campaign->getEditable()) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
            return;
        }

        if (!request()->getPost('email')) {
            notify()->addError(t('campaigns', 'Please specify the email address to where we should send the test email.'));
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid]);
            return;
        }

        $emails = (array)explode(',', (string)request()->getPost('email', ''));
        $emails = array_map('trim', $emails);
        $emails = array_unique($emails);
        $emails = array_slice($emails, 0, 100);

        $dsParams = ['useFor' => [DeliveryServer::USE_FOR_EMAIL_TESTS, DeliveryServer::USE_FOR_CAMPAIGNS]];

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $campaign, $dsParams);

        if (empty($server)) {
            notify()->addError(t('app', 'Email delivery is temporary disabled.'));
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid]);
            return;
        }

        foreach ($emails as $index => $email) {
            if (!FilterVarHelper::email($email)) {
                notify()->addError(t('email_templates', 'The email address {email} does not seem to be valid!', ['{email}' => html_encode($email)]));
                unset($emails[$index]);
            }
        }

        if (empty($emails)) {
            notify()->addError(t('campaigns', 'Cannot send using provided email address(es)!'));
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid]);
            return;
        }

        session()->add('campaignLastTestEmails', request()->getPost('email'));
        session()->add('campaignLastTestFromEmail', request()->getPost('from_email'));

        // 1.4.4
        $subscribers = [];
        foreach ($emails as $email) {
            if (array_key_exists($email, $subscribers)) {
                continue;
            }
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id' => $campaign->list->list_id,
                'email'   => $email,
                'status'  => ListSubscriber::STATUS_CONFIRMED,
            ]);
            if (empty($subscriber)) {
                $subscriber = ListSubscriber::model()->findByAttributes([
                    'list_id' => $campaign->list->list_id,
                    'status'  => ListSubscriber::STATUS_CONFIRMED,
                ]);
            }
            $subscribers[$email] = $subscriber;
        }
        //

        foreach ($emails as $email) {
            $subscriber      = !empty($subscribers[$email]) ? $subscribers[$email] : null;
            $fromEmailCustom = null;
            $fromNameCustom  = null;
            $replyToCustom   = null;

            $plainTextContent = $template->plain_text;
            $emailSubject     = $campaign->subject;
            $onlyPlainText    = !empty($template->only_plain_text) && $template->only_plain_text === CampaignTemplate::TEXT_YES;
            $emailContent     = !$onlyPlainText ? $template->content : $plainTextContent;
            $embedImages      = [];

            // @phpstan-ignore-next-line
            if (!$onlyPlainText && $server->getCanEmbedImages() && !empty($campaign->option) && !empty($campaign->option->embed_images) && $campaign->option->embed_images == CampaignOption::TEXT_YES) {
                [$emailContent, $embedImages] = CampaignHelper::embedContentImages($emailContent, $campaign);
            }

            $emailContent = (string)$emailContent;

            if (!empty($subscriber)) {

                // since 1.3.5.9
                // really blind check to see if it contains a tag
                if (strpos((string)$campaign->from_email, '[') !== false || strpos((string)$campaign->from_name, '[') !== false || strpos((string)$campaign->reply_to, '[') !== false) {
                    if (strpos((string)$campaign->from_email, '[') !== false) {
                        $searchReplace   = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_email, $campaign, $subscriber);
                        $fromEmailCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_email);
                        if (!FilterVarHelper::email($fromEmailCustom)) {
                            $fromEmailCustom = null;
                            $campaign->from_email = (string)$server->from_email; // @phpstan-ignore-line
                        }
                    }
                    if (strpos((string)$campaign->from_name, '[') !== false) {
                        $searchReplace  = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_name, $campaign, $subscriber);
                        $fromNameCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_name);
                    }
                    if (strpos((string)$campaign->reply_to, '[') !== false) {
                        $searchReplace = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->reply_to, $campaign, $subscriber);
                        $replyToCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->reply_to);
                        if (!FilterVarHelper::email($replyToCustom)) {
                            $replyToCustom = null;
                            $campaign->reply_to = (string)$server->from_email; // @phpstan-ignore-line
                        }
                    }
                }
                //

                if (!$onlyPlainText && !empty($campaign->option) && !empty($campaign->option->preheader)) {
                    $emailContent = CampaignHelper::injectPreheader($emailContent, $campaign->option->preheader, $campaign);
                }

                if (!$onlyPlainText && CampaignHelper::contentHasXmlFeed($emailContent)) {
                    $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, false, '', $server);
                }

                if (!$onlyPlainText && CampaignHelper::contentHasJsonFeed($emailContent)) {
                    $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, false, '', $server);
                }

                // 1.5.3
                if (!$onlyPlainText && CampaignHelper::hasRemoteContentTag($emailContent)) {
                    $emailContent = CampaignHelper::fetchContentForRemoteContentTag($emailContent, $campaign, $subscriber);
                }
                //

                $emailData  = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, false, $server);
                [, $_emailSubject, $emailContent] = $emailData;

                // since 1.3.5.3
                if (CampaignHelper::contentHasXmlFeed($_emailSubject)) {
                    $_emailSubject = CampaignXmlFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject, $server);
                }

                if (CampaignHelper::contentHasJsonFeed($_emailSubject)) {
                    $_emailSubject = CampaignJsonFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject, $server);
                }

                // 1.5.3
                if (CampaignHelper::hasRemoteContentTag($_emailSubject)) {
                    $_emailSubject = CampaignHelper::fetchContentForRemoteContentTag($_emailSubject, $campaign, $subscriber);
                }
                //

                if (!empty($_emailSubject)) {
                    $emailSubject = $_emailSubject;
                }
            }

            if ($onlyPlainText) {
                $emailContent = (string)preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailContent);
            }

            /** @var Customer $customer */
            $customer = customer()->getModel();
            $fromName = !empty($fromNameCustom) ? $fromNameCustom : $campaign->from_name;

            if (empty($fromName)) {
                $fromName = $customer->getFullName();
                if (!empty($customer->company)) {
                    $fromName = $customer->company->name;
                }
                if (empty($fromName)) {
                    $fromName = $customer->email;
                }
            }

            $fromEmail = (string)request()->getPost('from_email');
            if (!empty($fromEmail) && !FilterVarHelper::email($fromEmail)) {
                $fromEmail = null;
            }

            if (empty($fromEmail) && !empty($fromEmailCustom)) {
                $fromEmail = $fromEmailCustom;
            }

            if (CampaignHelper::isTemplateEngineEnabled()) {
                if (!$onlyPlainText && !empty($emailContent)) {
                    $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailContent, $campaign, $subscriber, $server);
                    $emailContent = CampaignHelper::parseByTemplateEngine($emailContent, $searchReplace);
                }
                if (!empty($emailSubject)) {
                    $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailSubject, $campaign, $subscriber, $server);
                    $emailSubject  = CampaignHelper::parseByTemplateEngine($emailSubject, $searchReplace);
                }
                if (!empty($plainTextContent)) {
                    $searchReplace   = CampaignHelper::getCommonTagsSearchReplace($plainTextContent, $campaign, $subscriber, $server);
                    $plainTextContent = CampaignHelper::parseByTemplateEngine($plainTextContent, $searchReplace);
                }
            }

            $params = [
                'to'            => $email,
                'fromName'      => $fromName,
                'subject'       => '*** ' . strtoupper(t('app', 'Test')) . ' *** ' . $emailSubject,
                'body'          => $onlyPlainText ? null : $emailContent,
                'embedImages'   => $embedImages,
                'plainText'     => $plainTextContent,
                'onlyPlainText' => $onlyPlainText,

                // since 1.3.5.9
                'fromEmailCustom' => $fromEmailCustom,
                'fromNameCustom'  => $fromNameCustom,
                'replyToCustom'   => $replyToCustom,
            ];

            if ($fromEmail) {
                $params['from'] = [$fromEmail => $fromName];
            }

            // @since 2.0.33
            $attachments = $campaign->attachments;
            if (!empty($attachments) && is_array($attachments)) {
                $params['attachments'] = [];
                foreach ($attachments as $attachment) {
                    // @phpstan-ignore-next-line
                    $file = (string)Yii::getPathOfAlias('root') . (string)$attachment->file;
                    if (is_file($file)) {
                        $params['attachments'][] = $file;
                    }
                }
            }

            $serverLog = null;
            $sent = false;
            for ($i = 0; $i < 3; ++$i) {
                // @phpstan-ignore-next-line
                if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN_TEST)->setDeliveryObject($campaign)->sendEmail($params)) {
                    break;
                }

                // @phpstan-ignore-next-line
                $serverLog = $server->getMailer()->getLog();
                sleep(1);

                /** @var DeliveryServer|null $server */
                $server = DeliveryServer::pickServer((int)$server->server_id, $campaign, $dsParams); // @phpstan-ignore-line

                if (empty($server)) {
                    break;
                }
            }

            if (!$sent) {
                notify()->addError(t('campaigns', 'Unable to send the test email to {email}!', [
                    '{email}' => html_encode($email),
                ]) . (!empty($serverLog) ? sprintf(' (%s)', $serverLog) : ''));
            } else {
                notify()->addSuccess(t('campaigns', 'Test email successfully sent to {email}!', [
                    '{email}' => html_encode($email),
                ]));
            }
        }

        $this->redirect(['campaigns/template', 'campaign_uid' => $campaign_uid]);
    }

    /**
     * List available list segments when choosing a list for a campaign
     *
     * @param int $list_id
     *
     * @return void
     * @throws CException
     */
    public function actionList_segments($list_id)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$list_id);
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

        $list = Lists::model()->find($criteria);
        if (empty($list)) {
            $this->renderJson(['segments' => []]);
            return;
        }

        $campaign = new Campaign();
        $campaign->list_id = (int)$list->list_id;
        $segments = $campaign->getSegmentsDropDownArray();

        $json = [];
        $json[] = [
            'segment_id' => '',
            'name'       => t('app', 'Choose'),
        ];

        foreach ($segments as $segment_id => $name) {
            $json[] = [
                'segment_id' => $segment_id,
                'name'       => html_encode($name),
            ];
        }

        $this->renderJson(['segments' => $json]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $list     = $campaign->list;
        $customer = $list->customer;
        $canCopy  = true;

        if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxCampaigns) {
                notify()->addWarning(t('lists', 'You have reached the maximum number of allowed campaigns.'));
                $canCopy = false;
            }
        }

        $copied = false;
        if ($canCopy) {
            $copied = $campaign->copy();
        }

        if ($copied) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully copied!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }

        $this->renderJson([
            'next' => !empty($copied) ? createUrl('campaigns/update', ['campaign_uid' => $copied->campaign_uid]) : '',
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if ($campaign->getRemovable()) {
            $campaign->delete();

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->campaignDeleted($campaign);
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['campaigns/' . $campaign->type]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $campaign,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPause_unpause($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        $campaign->pauseUnpause();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionResume_sending($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if ($campaign->getIsProcessing()) {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionMarksent($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $campaign->markAsSent();

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Run a bulk action against the campaigns
     *
     * @param string $type
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws Throwable
     */
    public function actionBulk_action($type = '')
    {
        // 1.4.5

        $action = (string)request()->getPost('bulk_action');

        /** @var array<string> $items */
        $items  = array_unique((array)request()->getPost('bulk_item', []));

        $returnRoute = ['campaigns/index'];
        $campaign    = new Campaign();
        if (in_array($type, $campaign->getTypesList())) {
            $returnRoute = ['campaigns/' . $type];
        }

        if ($action == Campaign::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }
                if (!$campaign->getRemovable()) {
                    continue;
                }
                $campaign->delete();
                $affected++;

                /** @var Customer $customer */
                $customer = customer()->getModel();

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->campaignDeleted($campaign);
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_COPY && count($items)) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            $affected = 0;
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }
                if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
                    $criteria = new CDbCriteria();
                    $criteria->compare('customer_id', (int)$customer->customer_id);
                    $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
                    $campaignsCount = Campaign::model()->count($criteria);
                    if ($campaignsCount >= $maxCampaigns) {
                        continue;
                    }
                }
                if (!$campaign->copy()) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_PAUSE_UNPAUSE && count($items)) {
            $affected = 0;
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }
                $campaign->pauseUnpause();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_MARK_SENT && count($items)) {
            $affected = 0;
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }
                if (!$campaign->markAsSent()) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_EXPORT_BASIC_STATS && count($items)) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            if ($customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
                $this->redirect($returnRoute);
            }

            // Set the download headers
            HeaderHelper::setDownloadHeaders('bulk-basic-stats-' . date('Y-m-d-h-i-s') . '.csv');

            $header = [
                t('campaign_reports', 'Name'),
                t('campaign_reports', 'Subject'),
                t('campaign_reports', 'Unique ID'),
                t('campaign_reports', 'Processed'),
                t('campaign_reports', 'Sent with success'),
                t('campaign_reports', 'Sent success rate'),
                t('campaign_reports', 'Send error'),
                t('campaign_reports', 'Send error rate'),
                t('campaign_reports', 'Unique opens'),
                t('campaign_reports', 'Unique open rate'),
                t('campaign_reports', 'All opens'),
                t('campaign_reports', 'All opens rate'),
                t('campaign_reports', 'Bounced back'),
                t('campaign_reports', 'Bounce rate'),
                t('campaign_reports', 'Hard bounce'),
                t('campaign_reports', 'Hard bounce rate'),
                t('campaign_reports', 'Soft bounce'),
                t('campaign_reports', 'Soft bounce rate'),
                t('campaign_reports', 'Unsubscribe'),
                t('campaign_reports', 'Unsubscribe rate'),
                t('campaign_reports', 'Total urls for tracking'),
                t('campaign_reports', 'Unique clicks'),
                t('campaign_reports', 'Unique clicks rate'),
                t('campaign_reports', 'All clicks'),
                t('campaign_reports', 'All clicks rate'),
            ];

            try {
                $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
                $csvWriter->insertOne($header);

                /** @var string $item */
                foreach ($items as $item) {
                    if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                        continue;
                    }

                    $row = [
                        $campaign->name,
                        $campaign->subject,
                        $campaign->campaign_uid,
                        $campaign->getStats()->getProcessedCount(true),
                        $campaign->getStats()->getDeliverySuccessCount(true),
                        $campaign->getStats()->getDeliverySuccessRate(true) . '%',
                        $campaign->getStats()->getDeliveryErrorCount(true),
                        $campaign->getStats()->getDeliveryErrorRate(true) . '%',
                        $campaign->getStats()->getUniqueOpensCount(true),
                        $campaign->getStats()->getUniqueOpensRate(true) . '%',
                        $campaign->getStats()->getOpensCount(true),
                        $campaign->getStats()->getOpensRate(true) . '%',
                        $campaign->getStats()->getBouncesCount(true),
                        $campaign->getStats()->getBouncesRate(true) . '%',
                        $campaign->getStats()->getHardBouncesCount(true),
                        $campaign->getStats()->getHardBouncesRate(true) . '%',
                        $campaign->getStats()->getSoftBouncesCount(true) . '%',
                        $campaign->getStats()->getSoftBouncesRate(true) . '%',
                        $campaign->getStats()->getUnsubscribesCount(true),
                        $campaign->getStats()->getUnsubscribesRate(true) . '%',
                    ];

                    if ($campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                        $row[] = $campaign->getStats()->getTrackingUrlsCount(true);
                        $row[] = $campaign->getStats()->getUniqueClicksCount(true);
                        $row[] = $campaign->getStats()->getUniqueClicksRate(true) . '%';
                        $row[] = $campaign->getStats()->getClicksCount(true);
                        $row[] = $campaign->getStats()->getClicksRate(true) . '%';
                    } else {
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }

                    $csvWriter->insertOne($row);
                }
            } catch (Exception $e) {
            }

            app()->end();
        } elseif ($action == Campaign::BULK_ACTION_SEND_TEST_EMAIL && count($items)) {
            if (!request()->getPost('recipients_emails')) {
                notify()->addError(t('campaigns', 'Please specify the email address to where we should send the test email.'));
                $this->redirect(request()->getPost('returnUrl', request()->getServer('HTTP_REFERER', $returnRoute)));
            }

            /** @var array $emails */
            $emails = (array)explode(',', (string)request()->getPost('recipients_emails', ''));
            $emails = array_map('trim', $emails);
            $emails = array_unique($emails);
            $emails = array_slice($emails, 0, 100);

            foreach ($emails as $index => $email) {
                if (!FilterVarHelper::email($email)) {
                    notify()->addError(t('campaigns', 'The email address {email} does not seem to be valid!', ['{email}' => html_encode($email)]));
                    unset($emails[$index]);
                }
            }

            if (empty($emails)) {
                notify()->addError(t('campaigns', 'Cannot send using provided email address(es)!'));
                $this->redirect(request()->getPost('returnUrl', request()->getServer('HTTP_REFERER', $returnRoute)));
            }

            session()->add('campaignLastTestEmails', request()->getPost('recipients_emails', ''));

            $affected = 0;
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }

                if (empty($campaign->template)) {
                    continue;
                }

                if ($campaign->getIsPendingDelete()) {
                    continue;
                }

                $dsParams = ['useFor' => [DeliveryServer::USE_FOR_EMAIL_TESTS, DeliveryServer::USE_FOR_CAMPAIGNS]];

                /** @var DeliveryServer|null $server */
                $server = DeliveryServer::pickServer(0, $campaign, $dsParams);
                if (empty($server)) {
                    continue;
                }

                // 1.4.4
                $subscribers = [];
                foreach ($emails as $email) {
                    if (array_key_exists($email, $subscribers)) {
                        continue;
                    }
                    $subscriber = ListSubscriber::model()->findByAttributes([
                        'list_id' => $campaign->list_id,
                        'email'   => $email,
                        'status'  => ListSubscriber::STATUS_CONFIRMED,
                    ]);
                    if (empty($subscriber)) {
                        $subscriber = ListSubscriber::model()->findByAttributes([
                            'list_id' => $campaign->list_id,
                            'status'  => ListSubscriber::STATUS_CONFIRMED,
                        ]);
                    }
                    $subscribers[$email] = $subscriber;
                }
                //

                $template = $campaign->template;

                foreach ($emails as $email) {
                    $subscriber      = !empty($subscribers[$email]) ? $subscribers[$email] : null;
                    $fromEmailCustom = null;
                    $fromNameCustom  = null;
                    $replyToCustom   = null;

                    $plainTextContent = $template->plain_text;
                    $emailSubject     = $campaign->subject;
                    $onlyPlainText    = !empty($template->only_plain_text) && $template->only_plain_text === CampaignTemplate::TEXT_YES;
                    $emailContent     = !$onlyPlainText ? $template->content : $plainTextContent;
                    $embedImages      = [];

                    // @phpstan-ignore-next-line
                    if (!$onlyPlainText && $server->getCanEmbedImages() && !empty($campaign->option) && !empty($campaign->option->embed_images) && $campaign->option->embed_images == CampaignOption::TEXT_YES) {
                        [$emailContent, $embedImages] = CampaignHelper::embedContentImages($emailContent, $campaign);
                    }

                    if (!empty($subscriber)) {

                        // since 1.3.5.9
                        // really blind check to see if it contains a tag
                        if (strpos((string)$campaign->from_email, '[') !== false || strpos((string)$campaign->from_name, '[') !== false || strpos((string)$campaign->reply_to, '[') !== false) {
                            if (strpos((string)$campaign->from_email, '[') !== false) {
                                $searchReplace   = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_email, $campaign, $subscriber);
                                $fromEmailCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_email);
                                if (!FilterVarHelper::email($fromEmailCustom)) {
                                    $fromEmailCustom = null;
                                    $campaign->from_email = (string)$server->from_email; // @phpstan-ignore-line
                                }
                            }
                            if (strpos((string)$campaign->from_name, '[') !== false) {
                                $searchReplace  = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_name, $campaign, $subscriber);
                                $fromNameCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_name);
                            }
                            if (strpos((string)$campaign->reply_to, '[') !== false) {
                                $searchReplace = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->reply_to, $campaign, $subscriber);
                                $replyToCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->reply_to);
                                if (!FilterVarHelper::email($replyToCustom)) {
                                    $replyToCustom = null;
                                    $campaign->reply_to = (string)$server->from_email; // @phpstan-ignore-line
                                }
                            }
                        }
                        //

                        if (!$onlyPlainText && !empty($campaign->option) && !empty($campaign->option->preheader)) {
                            $emailContent = CampaignHelper::injectPreheader($emailContent, $campaign->option->preheader, $campaign);
                        }

                        if (!$onlyPlainText && CampaignHelper::contentHasXmlFeed($emailContent)) {
                            $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, false, '', $server);
                        }

                        if (!$onlyPlainText && CampaignHelper::contentHasJsonFeed($emailContent)) {
                            $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, false, '', $server);
                        }

                        // 1.5.3
                        if (!$onlyPlainText && CampaignHelper::hasRemoteContentTag($emailContent)) {
                            $emailContent = CampaignHelper::fetchContentForRemoteContentTag($emailContent, $campaign, $subscriber);
                        }
                        //

                        $emailData  = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, false, $server);
                        [, $_emailSubject, $emailContent] = $emailData;

                        // since 1.3.5.3
                        if (CampaignHelper::contentHasXmlFeed($_emailSubject)) {
                            $_emailSubject = CampaignXmlFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject, $server);
                        }

                        if (CampaignHelper::contentHasJsonFeed($_emailSubject)) {
                            $_emailSubject = CampaignJsonFeedParser::parseContent($_emailSubject, $campaign, $subscriber, false, $emailSubject, $server);
                        }

                        // 1.5.3
                        if (CampaignHelper::hasRemoteContentTag($_emailSubject)) {
                            $_emailSubject = CampaignHelper::fetchContentForRemoteContentTag($_emailSubject, $campaign, $subscriber);
                        }
                        //

                        if (!empty($_emailSubject)) {
                            $emailSubject = $_emailSubject;
                        }
                    }

                    if ($onlyPlainText) {
                        $emailContent = (string)preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailContent);
                    }

                    /** @var Customer $customer */
                    $customer = customer()->getModel();

                    $fromName = !empty($fromNameCustom) ? $fromNameCustom : $campaign->from_name;

                    if (empty($fromName)) {
                        $fromName = $customer->getFullName();
                        if (!empty($customer->company)) {
                            $fromName = $customer->company->name;
                        }
                        if (empty($fromName)) {
                            $fromName = $customer->email;
                        }
                    }

                    $fromEmail = null;
                    if (!empty($fromEmailCustom)) {
                        $fromEmail = $fromEmailCustom;
                    }

                    if (CampaignHelper::isTemplateEngineEnabled()) {
                        if (!$onlyPlainText && !empty($emailContent)) {
                            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailContent, $campaign, $subscriber, $server);
                            $emailContent = CampaignHelper::parseByTemplateEngine($emailContent, $searchReplace);
                        }
                        if (!empty($emailSubject)) {
                            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailSubject, $campaign, $subscriber, $server);
                            $emailSubject  = CampaignHelper::parseByTemplateEngine($emailSubject, $searchReplace);
                        }
                        if (!empty($plainTextContent)) {
                            $searchReplace   = CampaignHelper::getCommonTagsSearchReplace($plainTextContent, $campaign, $subscriber, $server);
                            $plainTextContent = CampaignHelper::parseByTemplateEngine($plainTextContent, $searchReplace);
                        }
                    }

                    $params = [
                        'to'            => $email,
                        'fromName'      => $fromName,
                        'subject'       => '*** ' . strtoupper(t('app', 'Test')) . ' *** ' . $emailSubject,
                        'body'          => $onlyPlainText ? null : $emailContent,
                        'embedImages'   => $embedImages,
                        'plainText'     => $plainTextContent,
                        'onlyPlainText' => $onlyPlainText,

                        // since 1.3.5.9
                        'fromEmailCustom' => $fromEmailCustom,
                        'fromNameCustom'  => $fromNameCustom,
                        'replyToCustom'   => $replyToCustom,
                    ];

                    if ($fromEmail) {
                        $params['from'] = [$fromEmail => $fromName];
                    }

                    $sent = false;
                    for ($i = 0; $i < 3; ++$i) {
                        // @phpstan-ignore-next-line
                        if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN_TEST)->setDeliveryObject($campaign)->sendEmail($params)) {
                            break;
                        }

                        /** @var DeliveryServer|null $server */
                        $server = DeliveryServer::pickServer((int)$server->server_id, $campaign, $dsParams); // @phpstan-ignore-line

                        if (empty($server)) {
                            break;
                        }
                    }

                    if (!$sent) {
                        notify()->addError(t('campaigns', 'Campaign {campaign}: Unable to send the test email to {email}!', [
                            '{campaign}' => $campaign->name . '[' . $campaign->campaign_uid . '] ',
                            '{email}'    => html_encode($email),
                        ]));
                    } else {
                        notify()->addSuccess(t('campaigns', 'Campaign {campaign}: Test email successfully sent to {email}!', [
                            '{campaign}' => $campaign->name . '[' . $campaign->campaign_uid . '] ',
                            '{email}'    => html_encode($email),
                        ]));
                    }

                    $affected++;
                }
            }

            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_SHARE_CAMPAIGN_CODE && count($items)) {
            $affected = 0;
            $success  = false;
            $campaignsIds = [];

            // Collect the campaign ids
            /** @var string $item */
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid((string)$item))) {
                    continue;
                }
                $campaignsIds[] = (int)$campaign->campaign_id;
            }

            $campaignShareCode = new CampaignShareCode();

            if (!empty($campaignsIds)) {
                $transaction = db()->beginTransaction();

                try {
                    if (!$campaignShareCode->save()) {
                        throw new Exception(t('campaigns', 'Could not save the sharing code'));
                    }

                    foreach ($campaignsIds as $campaignId) {
                        $campaignShareCodeToCampaign              = new CampaignShareCodeToCampaign();
                        $campaignShareCodeToCampaign->code_id     = (int)$campaignShareCode->code_id;
                        $campaignShareCodeToCampaign->campaign_id = (int)$campaignId;

                        if (!$campaignShareCodeToCampaign->save()) {
                            throw new Exception(t('campaigns', 'Could not save the sharing code to campaign'));
                        }

                        $affected++;
                    }

                    $transaction->commit();
                    $success = true;
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    $transaction->rollback();
                }
            }

            if ($success) {
                notify()->addSuccess(t('campaigns', 'The sharing code is: {code}', [
                    '{code}' => sprintf('<strong>%s</strong>', $campaignShareCode->code_uid),
                ]));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', $returnRoute);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * @param string $campaign_uid
     * @param int $attachment_id
     *
     * @return void
     * @throws CDbException
     * @throws CHttpException
     * @throws CException
     */
    public function actionRemove_attachment($campaign_uid, $attachment_id)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $attachment = CampaignAttachment::model()->findByAttributes([
            'attachment_id' => (int)$attachment_id,
            'campaign_id'   => (int)$campaign->campaign_id,
        ]);

        if (!empty($attachment)) {
            $attachment->delete();
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your campaign attachment was successfully removed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionSync_datetime()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $timeZoneDateTime   = date('Y-m-d H:i:s', (int)strtotime((string)request()->getQuery('date', date('Y-m-d H:i:s'))));
        $timeZoneTimestamp  = (int)strtotime($timeZoneDateTime);
        $localeDateTime     = dateFormatter()->formatDateTime($timeZoneTimestamp, 'short', 'short');

        // since the date is already in customer timezone we need to convert it back to utc
        $sourceTimeZone      = new DateTimeZone($customer->timezone);
        $destinationTimeZone = new DateTimeZone(app()->getTimeZone());
        $dateTime            = new DateTime($timeZoneDateTime, $sourceTimeZone);
        $dateTime->setTimezone($destinationTimeZone);
        $utcDateTime = $dateTime->format('Y-m-d H:i:s');

        $this->renderJson([
            'localeDateTime'  => $localeDateTime,
            'utcDateTime'     => $utcDateTime,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionGoogle_utm_tags($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        if (empty($campaign->template) || empty($campaign->template->content)) {
            notify()->addError(t('campaigns', 'Please use a template for this campaign in order to insert the google utm tags!'));
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $pattern = (string)request()->getPost('google_utm_pattern');
        if (empty($pattern)) {
            notify()->addError(t('campaigns', 'Please specify a pattern in order to insert the google utm tags!'));
            $this->redirect(['campaigns/template', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $campaign->template->content = CampaignHelper::injectGoogleUtmTagsIntoTemplate($campaign->template->content, $pattern);
        $campaign->template->save(false);

        notify()->addSuccess(t('campaigns', 'The google utm tags were successfully inserted into your template!'));
        $this->redirect(['campaigns/template', 'campaign_uid' => $campaign->campaign_uid]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionShare_reports($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        $shareReports = $campaign->shareReports;
        $shareReports->attributes  = (array)request()->getPost($shareReports->getModelName(), []);
        $shareReports->campaign_id = (int)$campaign->campaign_id;

        if (!$shareReports->save()) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  CHtml::errorSummary($shareReports, null, null, ['class' => '']),
            ]);
            return;
        }

        $this->renderJson([
            'result'  => 'success',
            'message' =>  t('app', 'Your form has been successfully saved!'),
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionShare_reports_send_email($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        $shareReports        = $campaign->shareReports;
        $shareReportsEnabled = $shareReports->share_reports_enabled == CampaignOptionShareReports::TEXT_YES;
        $shareReports->setScenario('send-email');

        $shareReports->attributes  = (array)request()->getPost($shareReports->getModelName(), []);
        $shareReports->campaign_id = (int)$campaign->campaign_id;

        if (!$shareReports->validate()) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  CHtml::errorSummary($shareReports, null, null, ['class' => '']),
            ]);
            return;
        }

        if (!$shareReportsEnabled) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'It seems share reports is disabled for this campaign, did you forget to save changes?'),
            ]);
            return;
        }

        $dsParams = ['useFor' => DeliveryServer::USE_FOR_CAMPAIGNS];

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $campaign, $dsParams);
        if (empty($server)) {

            // since 2.1.4
            $this->handleShareReportsSendEmailFail($campaign);

            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'Email delivery is disabled at the moment, please try again later!'),
            ]);
            return;
        }
        $emails = CommonHelper::getArrayFromString((string)$shareReports->share_reports_emails);
        $sent = 0;
        $sentEmails = [];
        foreach ($emails as $email) {
            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'campaign-share-reports-access',
                [
                    'to'      => [$email => $email],
                    'subject' => t('lists', 'Campaign reports access!'),
                ],
                [
                    '[CAMPAIGN_NAME]'             => $campaign->name,
                    '[CAMPAIGN_REPORTS_URL]'      => $shareReports->getShareUrl(),
                    '[CAMPAIGN_REPORTS_PASSWORD]' => $shareReports->share_reports_password,
                ]
            );

            try {
                for ($i = 0; $i < 3; ++$i) {
                    // @phpstan-ignore-next-line
                    if ($server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN)->setDeliveryObject($campaign)->sendEmail($params)) {
                        $sentEmails[] = $email;
                        $sent++;
                        break;
                    }

                    /** @var DeliveryServer|null $server */
                    $server = DeliveryServer::pickServer((int)$server->server_id, $campaign, $dsParams); // @phpstan-ignore-line

                    if (empty($server)) {
                        break;
                    }
                }
            } catch (Exception $e) {
            }
        }

        if (!$sent) {

            // since 2.1.4
            $this->handleShareReportsSendEmailFail($campaign);

            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'Unable to send the emails at this time, please try again later!'),
            ]);
            return;
        }

        $message = t('campaigns', 'The emails have been sent successfully!');
        if ($sent < count($emails)) {
            $message = t('campaigns', 'Following emails failed sending! {emails}', [
                '{emails}' => html_encode((string)implode(', ', array_diff($emails, $sentEmails))),
            ]);
        }

        $this->renderJson([
            'result'  => 'success',
            'message' =>  $message,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionResend_giveups($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        if (!request()->getIsAjaxRequest() || !request()->getIsPostRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        if (!$campaign->getIsSent()) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'Resending to giveups only works for sent campaigns!'),
            ]);
            return;
        }

        if (empty($campaign->option->giveup_count)) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'It seems this campaign has no giveups!'),
            ]);
            return;
        }

        $queued = CampaignResendGiveupQueue::model()->countByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if ($queued) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'It seems this campaign has already been queued to resend to giveups!'),
            ]);
            return;
        }

        $queue = new CampaignResendGiveupQueue();
        $queue->campaign_id = (int)$campaign->campaign_id;
        $queue->save(false);

        $this->renderJson([
            'result'  => 'success',
            'message' =>  t('campaigns', 'The campaigns has been queued successfully, it will start sending in a few minutes!'),
        ]);
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function actionExport($type = '')
    {
        $attributes = [
            'customer_id' => (int)customer()->getId(),
        ];

        if ($type) {
            $attributes['type'] = $type;
        }

        $models = Campaign::model()->findAllByAttributes($attributes);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('campaigns.csv');

        try {
            $csvWriter  = League\Csv\Writer::createFromPath('php://output', 'w');
            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

            /** @var callable $callback */
            $callback   = [$models[0], 'getAttributeLabel'];
            $attributes = array_map($callback, array_keys($attributes));

            $attributes = CMap::mergeArray($attributes, [
                $models[0]->getAttributeLabel('send_group_id'),
                $models[0]->getAttributeLabel('group_id'),
                $models[0]->getAttributeLabel('list_id'),
                $models[0]->getAttributeLabel('segment_id'),
            ]);

            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $attributes = CMap::mergeArray($attributes, [
                    'send_group'    => $model->send_group_id ? $model->sendGroup->name : '',
                    'group'         => $model->group_id ? $model->group->name : '',
                    'list'          => $model->list_id ? $model->list->name : '',
                    'segment'       => $model->segment_id ? $model->segment->name : '',
                ]);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionImport_from_share_code()
    {
        $returnRoute = ['campaigns/index'];
        if (!request()->getIsPostRequest()) {
            $this->redirect($returnRoute);
        }

        $shareCode = new CampaignShareCodeImport();
        $shareCode->attributes  = (array)request()->getPost($shareCode->getModelName(), []);
        $shareCode->customer_id = (int)customer()->getId();

        if (!$shareCode->validate()) {
            notify()->addError($shareCode->shortErrors->getAllAsString());
            $this->redirect($returnRoute);
        }

        /** @var CampaignShareCode $campaignShareCodeModel */
        $campaignShareCodeModel = $shareCode->getCampaignShareCode();

        $success = false;
        $message = '';

        $transaction = db()->beginTransaction();

        try {
            if (!($campaignModels = $campaignShareCodeModel->campaigns)) {
                throw new Exception(t('campaigns', 'Could not find any campaign to share'));
            }

            $campaigns = [];
            foreach ($campaignModels as $campaignModel) {
                if ($campaignModel->getIsPendingDelete()) {
                    continue;
                }
                $campaigns[] = $campaignModel;
            }

            if (empty($campaigns)) {
                throw new Exception(t('campaigns', 'Could not find any campaign to share'));
            }

            foreach ($campaigns as $campaign) {
                if (!($newCampaign = $campaign->copy(false))) {
                    throw new Exception(t('campaigns', 'Could not copy the shared campaign'));
                }

                $newCampaign->customer_id = (int)$shareCode->customer_id;
                $newCampaign->list_id     = (int)$shareCode->list_id;

                if (!$newCampaign->save()) {
                    throw new Exception(t('campaigns', 'Could not save the shared campaign'));
                }
            }

            $campaignShareCodeModel->used = CampaignShareCode::TEXT_YES;
            if (!$campaignShareCodeModel->save()) {
                throw new Exception(t('campaigns', 'Could not update the campaign shared code status'));
            }

            $transaction->commit();
            $success = true;
        } catch (Exception $e) {
            $transaction->rollback();
            $message = $e->getMessage();
        }

        if (!$success) {
            notify()->addError($message);
            $this->redirect($returnRoute);
        }

        notify()->addSuccess(t('campaigns', 'Successful imported the shared campaigns'));
        $this->redirect($returnRoute);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionQuick_view($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        $abTest = null;
        if ($campaign->getCanDoAbTest()) {
            $abTest = CampaignAbtest::model()->findByAttributes([
                'campaign_id'   => $campaign->campaign_id,
                'enabled'       => CampaignAbtest::TEXT_YES,
                'status'        => [CampaignAbtest::STATUS_ACTIVE, CampaignAbtest::STATUS_COMPLETE],
            ]);
        }

        $this->renderPartial('_quick-view', compact('campaign', 'abTest'));
    }

    /**
     * Compare campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionCompare()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/index']);
        }

        $items = array_unique((array)request()->getPost('bulk_item', []));
        $items = array_slice($items, 0, 5);

        $criteria = new CDbCriteria();
        $criteria->addInCondition('campaign_uid', $items);
        $criteria->order = 'campaign_id ASC';
        $campaigns = Campaign::model()->findAll($criteria);

        $this->renderPartial('_compare', compact('campaigns'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws Exception
     */
    public function actionEstimate_recipients_count($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        $count      = $campaign->countSubscribers();
        $messages   = [];

        $messages[] = t('campaigns', 'This campaign will target approximately {count} subscribers.', [
            '{count}' => sprintf('<strong>%s</strong>', numberFormatter()->formatDecimal($count)),
        ]);

        if ($campaign->getIsAutoresponder()) {
            $messages[] = t('campaigns', 'Since this is an Autoresponder campaign, the recipients count will be adjusted while the campaign is running.');
            if ($count === 0) {
                $messages[] = t('campaigns', 'For Autoresponders it is perfectly normal for the number of recipients to be 0.');
                $messages[] = t('campaigns', 'By default, Autoresponders target only subscribers added to your email list after the Autoresponder itself has been created.');
                $messages[] = t('campaigns', 'If you need to target more subscribers from the start, include imported and/or current subscribers.');
            }
        }

        if (!empty($campaign->send_group_id)) {
            $messages[] = t('campaigns', 'Since this campaign is part of a Send Group, the recipients count will be adjusted while the campaign is running.');
            $messages[] = t('campaigns', 'Because the way Send Groups are optimised for sending, it is not possible to tell the exact number of recipients at this time.');
        }

        $this->renderJson([
            'messages' => $messages,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign
     * @throws CHttpException
     */
    public function loadCampaignModel(string $campaign_uid): Campaign
    {
        /** @var Campaign|null $model */
        $model = $this->loadCampaignByUid($campaign_uid);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsPendingDelete()) {
            $this->redirect(['campaigns/' . $model->type]);
        }

        if (empty($model->option)) {
            $option = new CampaignOption();
            $option->campaign_id = (int)$model->campaign_id;
            $model->addRelatedRecord('option', $option, false);
        }

        return $model;
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     */
    public function loadCampaignByUid(string $campaign_uid): ?Campaign
    {
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        return $model;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _setEditorOptions(CEvent $event)
    {
        if ($event->params['attribute'] == 'content') {
            $options = [];
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }
            $options['id'] = CHtml::activeId($event->sender->owner, 'content');
            $options['fullPage'] = true;
            $options['allowedContent'] = true;
            $options['contentsCss'] = [];
            $options['height'] = 800;

            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _setRandomContentEditorOptions(CEvent $event)
    {
        if ($event->params['attribute'] == 'content') {
            $options = [];
            if ($event->params['htmlOptions']->contains('wysiwyg_editor_options')) {
                $options = (array)$event->params['htmlOptions']->itemAt('wysiwyg_editor_options');
            }
            $options['id']          = CHtml::activeId($event->sender->owner, 'content');
            $options['toolbar']     = 'Simple';
            $options['contentsCss'] = [];
            $options['height']      = 100;

            $event->params['htmlOptions']->add('wysiwyg_editor_options', $options);
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index', 'create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return void
     */
    protected function handleShareReportsSendEmailFail(Campaign $campaign): void
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $messageTitle   = 'Unable to send email';
        $messageContent = 'Sharing the reports for the {campaign} campaign has failed because the system was not able to find a suitable delivery server to send the email';

        try {
            $message = new CustomerMessage();
            $message->customer_id = (int)$campaign->customer_id;
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{campaign}' => CHtml::link($campaign->name, $optionUrl->getCustomerUrl('campaigns/' . $campaign->campaign_uid . '/overview')),
            ];
            $message->save();

            $message = new UserMessage();
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{campaign}' => CHtml::link($campaign->name, $optionUrl->getBackendUrl('campaigns/index?Campaign[campaign_uid]=' . $campaign->campaign_uid)),
            ];
            $message->broadcast();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }
}
