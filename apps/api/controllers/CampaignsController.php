<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignsController
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
     * @return array
     */
    public function accessRules()
    {
        return [
            // allow all authenticated users on all actions
            ['allow', 'users' => ['@']],
            // deny all rule.
            ['deny'],
        ];
    }

    /**
     * Handles the listing of the campaigns.
     * The listing is based on page number and number of campaigns per page.
     * This action will produce a valid ETAG for caching purposes.
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $perPage    = (int)request()->getQuery('per_page', 10);
        $page       = (int)request()->getQuery('page', 1);
        $maxPerPage = 50;
        $minPerPage = 10;

        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }

        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        if ($page < 1) {
            $page = 1;
        }

        $data = [
            'count'         => null,
            'total_pages'   => null,
            'current_page'  => null,
            'next_page'     => null,
            'prev_page'     => null,
            'records'       => [],
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        $count = Campaign::model()->count($criteria);

        if ($count == 0) {
            $this->renderJson([
                'status'    => 'success',
                'data'      => $data,
            ]);
            return;
        }

        $totalPages = ceil($count / $perPage);

        $data['count']          = $count;
        $data['current_page']   = $page;
        $data['next_page']      = $page < $totalPages ? $page + 1 : null;
        $data['prev_page']      = $page > 1 ? $page - 1 : null;
        $data['total_pages']    = $totalPages;

        $criteria->order    = 't.campaign_id DESC';
        $criteria->limit    = $perPage;
        $criteria->offset   = ($page - 1) * $perPage;

        /** @var Campaign[] $campaigns */
        $campaigns = Campaign::model()->findAll($criteria);

        foreach ($campaigns as $campaign) {
            $record = $campaign->getAttributes(['campaign_uid', 'type', 'name', 'status']);

            // since 1.5.2
            $record['group'] = [];
            if (!empty($campaign->group_id)) {
                $record['group'] = $campaign->group->getAttributes(['group_uid', 'name']);
            }

            $data['records'][] = $record;
        }

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the listing of a single campaign.
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionView($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        /** @var array $record */
        $record = $campaign->getAttributes(['campaign_uid', 'name', 'type', 'from_name', 'from_email', 'to_name', 'reply_to', 'subject', 'status']);

        $record['date_added']   = $campaign->dateAdded;
        $record['send_at']      = $campaign->sendAt;
        $record['list']         = $campaign->list->getAttributes(['list_uid', 'name']);
        $record['list']['subscribers_count'] = $campaign->list->confirmedSubscribersCount;

        $record['segment'] = [];
        if (!empty($campaign->segment)) {
            $record['segment'] = $campaign->segment->getAttributes(['segment_uid', 'name']);
            $record['segment']['subscribers_count'] = $campaign->segment->countSubscribers();
        }

        // since 1.5.2
        $record['group'] = [];
        if (!empty($campaign->group_id)) {
            $record['group'] = $campaign->group->getAttributes(['group_uid', 'name']);
        }

        $data = [
            'record' => $record,
        ];

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * Handles the creation of a new campaign.
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Customer $customer */
        $customer = user()->getModel();

        if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
            $campaignsCount = Campaign::model()->count($criteria);
            if ($campaignsCount >= $maxCampaigns) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'You have reached the maximum number of allowed campaigns.'),
                ], 403);
                return;
            }
        }

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
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'You have reached the maximum number of allowed active campaigns.'),
                ], 403);
                return;
            }
        }

        $attributes = (array)request()->getPost('campaign', []);
        $campaign   = new Campaign();
        $campaignOption = new CampaignOption();

        // since 1.3.4.8
        if (isset($attributes['group_uid'])) {
            $campaignGroup = CampaignGroup::model()->findByAttributes(['group_uid' => $attributes['group_uid']]);
            unset($attributes['group_uid']);
            if (!empty($campaignGroup)) {
                $attributes['group_id'] = (int)$campaignGroup->group_id;
            }
        }

        /** @var CAttributeCollection $data */
        $data = $this->getData();
        $data->add('campaign', $campaign);

        $campaign->onBeforeValidate = [$this, '_beforeValidate'];
        $campaign->onRules          = [$this, '_setValidationRules'];
        $campaign->attributes       = $attributes;
        $campaign->customer_id      = (int)$customer->customer_id;

        if (!$campaign->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $campaign->shortErrors->getAll(),
            ], 422);
            return;
        }

        if (empty($attributes['list_uid'])) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a list for this campaign.'),
            ], 422);
            return;
        }

        /** @var Lists|null $list */
        $list = $this->loadListByUid((string)$attributes['list_uid']);

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Provided list does not exist.'),
            ], 422);
            return;
        }
        $campaign->list_id = (int)$list->list_id;

        if (!empty($attributes['segment_uid'])) {
            $segment = ListSegment::model()->findByAttributes([
                'segment_uid'   => $attributes['segment_uid'],
                'list_id'       => (int)$list->list_id,
            ]);

            if (empty($segment)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Provided list segment does not exist.'),
                ], 422);
                return;
            }

            $campaign->segment_id = (int)$segment->segment_id;
        }

        // set the campaign options, fallback on defaults
        if (!empty($attributes['options']) && is_array($attributes['options'])) {
            foreach ($attributes['options'] as $name => $value) {
                if ($campaignOption->hasAttribute($name)) {
                    $campaignOption->setAttribute($name, $value);
                }
            }
        }

        $template       = new CampaignTemplate();
        $templateAttr   = !empty($attributes['template']) && is_array($attributes['template']) ? $attributes['template'] : [];

        $template->name            = !empty($templateAttr['name']) ? $templateAttr['name'] : '';
        $template->content         = '';
        $template->auto_plain_text = !empty($templateAttr['auto_plain_text']) && $templateAttr['auto_plain_text'] == CampaignTemplate::TEXT_NO ? CampaignTemplate::TEXT_NO : CampaignTemplate::TEXT_YES;
        $template->plain_text      = !empty($templateAttr['plain_text']) && $campaignOption->plain_text_email == CampaignOption::TEXT_YES ? (string)base64_decode((string)$templateAttr['plain_text']) : '';

        if (!empty($templateAttr['template_uid'])) {
            $_template = CustomerEmailTemplate::model()->findByAttributes([
                'template_uid'  => $templateAttr['template_uid'],
                'customer_id'   => (int)$customer->customer_id,
            ]);

            if (empty($_template)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Provided template does not exist.'),
                ], 422);
                return;
            }

            $template->name    = (string)$_template->name;
            $template->content = (string)$_template->content;
        }

        if (empty($template->content) && !empty($templateAttr['content'])) {
            $template->content = (string)base64_decode((string)$templateAttr['content']);
        }

        if (empty($template->content) && !empty($templateAttr['archive'])) {
            $archivePath    = FileSystemHelper::getTmpDirectory() . '/' . StringHelper::random() . '.zip';
            $archiveContent = (string)base64_decode((string)$templateAttr['archive']);

            if (empty($archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'It does not seem that you have selected an archive.'),
                ], 422);
                return;
            }

            // http://www.garykessler.net/library/file_sigs.html
            $magicNumbers   = ['504B0304'];
            $substr         = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
            $firstBytes     = strtoupper(bin2hex($substr((string)$archiveContent, 0, 4)));

            if (!in_array($firstBytes, $magicNumbers)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Your archive does not seem to be a valid zip file.'),
                ], 422);
                return;
            }

            if (!file_put_contents($archivePath, $archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Cannot write archive in the temporary location.'),
                ], 422);
                return;
            }

            $_FILES['archive'] = [
                'name'      => basename($archivePath),
                'type'      => 'application/zip',
                'tmp_name'  => $archivePath,
                'error'     => 0,
                'size'      => filesize($archivePath),
            ];

            $archiveTemplate = new CampaignEmailTemplateUpload('upload');
            $archiveTemplate->archive = CUploadedFile::getInstanceByName('archive');

            if (!$archiveTemplate->validate()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $archiveTemplate->shortErrors->getAll(),
                ], 422);
                return;
            }

            $template->content = 'DUMMY DATA, IF YOU SEE THIS, SOMETHING WENT WRONG FROM THE API CALL!';
        }

        if (empty($template->content)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a template for your campaign.'),
            ], 422);
            return;
        }

        // since 1.3.4.8
        // delivery servers for this campaign - start
        $deliveryServers = [];
        if (isset($attributes['delivery_servers']) && $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes') {
            if (!is_array($attributes['delivery_servers'])) {
                $attributes['delivery_servers'] = explode(',', (string)$attributes['delivery_servers']);
            }
            $attributes['delivery_servers'] = array_map('trim', $attributes['delivery_servers']);
            $attributes['delivery_servers'] = array_map('intval', $attributes['delivery_servers']);
            $_deliveryServers = $customer->getAvailableDeliveryServers();
            $servers = [];
            foreach ($_deliveryServers as $srv) {
                $servers[] = (int)$srv->server_id;
            }
            foreach ($attributes['delivery_servers'] as $serverId) {
                if (in_array($serverId, $servers)) {
                    $deliveryServers[] = $serverId;
                }
            }
            unset($_deliveryServers, $servers);
        }
        // delivery servers for this campaign - end

        $transaction = db()->beginTransaction();
        try {

            // since the date is already in customer timezone we need to convert it back to utc
            $sourceTimeZone         = new DateTimeZone($customer->timezone);
            $destinationTimeZone    = new DateTimeZone(app()->getTimeZone());

            $dateTime = new DateTime((string)$campaign->send_at, $sourceTimeZone);
            $dateTime->setTimezone($destinationTimeZone);
            $campaign->send_at  = (string)$dateTime->format('Y-m-d H:i:s');
            $campaign->status   = Campaign::STATUS_PENDING_SENDING;

            // since 1.3.6.2
            $allowedStatuses = [Campaign::STATUS_PENDING_SENDING, Campaign::STATUS_DRAFT, Campaign::STATUS_PAUSED];
            if (isset($attributes['status']) && in_array($attributes['status'], $allowedStatuses)) {
                $campaign->status = $attributes['status'];
            }

            if (!$campaign->save()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $campaign->shortErrors->getAll(),
                ], 422);
                return;
            }

            $campaignOption->campaign_id = (int)$campaign->campaign_id;
            if (!$campaignOption->save()) {
                $transaction->rollback();
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $campaignOption->shortErrors->getAll(),
                ], 422);
                return;
            }

            if (!empty($archiveTemplate)) {
                $archiveTemplate->customer_id = (int)$customer->customer_id;
                $archiveTemplate->campaign    = $campaign;

                if (!$archiveTemplate->uploader->handleUpload()) {
                    $transaction->rollback();
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => $archiveTemplate->shortErrors->getAll(),
                    ], 422);
                    return;
                }

                $template->content  = (string)$archiveTemplate->content;
            }

            if (empty($template->plain_text) && $template->auto_plain_text == CampaignTemplate::TEXT_YES) {
                $template->plain_text = (string)CampaignHelper::htmlToText((string)$template->content);
            }

            if ($template->plain_text) {
                $template->plain_text = (string)ioFilter()->purify($template->plain_text);
            }

            $template->campaign_id = (int)$campaign->campaign_id;

            if (!$template->save()) {
                $transaction->rollback();
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $template->shortErrors->getAll(),
                ], 422);
                return;
            }

            // since 1.3.4.8
            if (!empty($deliveryServers)) {
                foreach ($deliveryServers as $serverId) {
                    $campaignToDeliveryServer = new CampaignToDeliveryServer();
                    $campaignToDeliveryServer->campaign_id = (int)$campaign->campaign_id;
                    $campaignToDeliveryServer->server_id   = (int)$serverId;
                    $campaignToDeliveryServer->save();
                }
            }

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            $this->renderJson([
                'status'    => 'error',
                'error'     => $e->getMessage(),
            ], 422);
            return;
        }

        $this->renderJson([
            'status'        => 'success',
            'campaign_uid'  => $campaign->campaign_uid,
        ], 201);
    }

    /**
     * Handles the updating of an existing campaign.
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionUpdate($campaign_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => 'Requested campaign does not exist.',
            ], 404);
            return;
        }

        if (!$campaign->getEditable()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => 'This campaign is not ediable.',
            ], 422);
            return;
        }

        /** @var CampaignOption $campaignOption */
        $campaignOption = new CampaignOption();
        if (!empty($campaign->option)) {
            $campaignOption = $campaign->option;
        }
        $campaignOption->campaign_id = (int)$campaign->campaign_id;

        $this->setData('campaign', $campaign);

        $sendAt     = $campaign->send_at;
        $attributes = (array)request()->getPut('campaign', []);

        /** @var Customer $customer */
        $customer = user()->getModel();

        // since 1.3.4.8
        if (isset($attributes['group_uid'])) {
            $campaignGroup = CampaignGroup::model()->findByAttributes(['group_uid' => $attributes['group_uid']]);
            unset($attributes['group_uid']);
            if (!empty($campaignGroup)) {
                $attributes['group_id'] = (int)$campaignGroup->group_id;
            }
        }

        $campaign->onBeforeValidate = [$this, '_beforeValidate'];
        $campaign->onRules          = [$this, '_setValidationRules'];
        $campaign->attributes       = $attributes;
        $campaign->customer_id      = (int)user()->getId();

        if (!$campaign->validate()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => $campaign->shortErrors->getAll(),
            ], 422);
            return;
        }

        /** @var Lists $list */
        $list = !empty($campaign->list) ? $campaign->list : null;
        if (!empty($attributes['list_uid'])) {
            /** @var Lists|null $list */
            $list = $this->loadListByUid((string)$attributes['list_uid']);
        }

        if (empty($list)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a list for this campaign.'),
            ], 422);
            return;
        }
        $campaign->list_id = (int)$list->list_id;

        if (!empty($attributes['segment_uid'])) {
            $segment = ListSegment::model()->findByAttributes([
                'segment_uid'   => $attributes['segment_uid'],
                'list_id'       => (int)$list->list_id,
            ]);

            if (empty($segment)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Provided list segment does not exist.'),
                ], 422);
                return;
            }

            $campaign->segment_id = (int)$segment->segment_id;
        }

        // set the campaign options, fallback on defaults
        if (!empty($attributes['options']) && is_array($attributes['options'])) {
            foreach ($attributes['options'] as $name => $value) {
                if ($campaignOption->hasAttribute($name)) {
                    $campaignOption->setAttribute($name, $value);
                }
            }
        }

        $template       = !empty($campaign->template) ? $campaign->template : new CampaignTemplate();
        $templateAttr   = !empty($attributes['template']) && is_array($attributes['template']) ? $attributes['template'] : [];
        $tempContent    = $template->content;

        $template->name    = !empty($templateAttr['name']) ? $templateAttr['name'] : '';
        $template->content = '';

        if (!empty($templateAttr['auto_plain_text'])) {
            $template->auto_plain_text = $templateAttr['auto_plain_text'] == CampaignTemplate::TEXT_NO ? CampaignTemplate::TEXT_NO : CampaignTemplate::TEXT_YES;
        }

        if (!empty($templateAttr['plain_text']) && $campaignOption->plain_text_email == CampaignOption::TEXT_YES) {
            $template->plain_text = (string)base64_decode((string)$templateAttr['plain_text']);
        }

        if (!empty($templateAttr['minify'])) {
            $template->minify = $templateAttr['minify'] == CampaignTemplate::TEXT_YES ? CampaignTemplate::TEXT_YES : CampaignTemplate::TEXT_NO;
        }

        if (!empty($templateAttr['template_uid'])) {
            $_template = CustomerEmailTemplate::model()->findByAttributes([
                'template_uid'  => $templateAttr['template_uid'],
                'customer_id'   => (int)user()->getId(),
            ]);

            if (empty($_template)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Provided template does not exist.'),
                ], 422);
                return;
            }

            $template->content = (string)$_template->content;
        }

        if (empty($template->content) && !empty($templateAttr['content'])) {
            $template->content = (string)base64_decode((string)$templateAttr['content']);
        }

        if (empty($template->content) && !empty($templateAttr['archive'])) {
            $archivePath = FileSystemHelper::getTmpDirectory() . '/' . StringHelper::random() . '.zip';
            $archiveContent = (string)base64_decode((string)$templateAttr['archive']);

            if (empty($archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'It does not seem that you have selected an archive.'),
                ], 422);
                return;
            }

            // http://www.garykessler.net/library/file_sigs.html
            $magicNumbers   = ['504B0304'];
            $substr         = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
            $firstBytes     = strtoupper(bin2hex($substr((string)$archiveContent, 0, 4)));

            if (!in_array($firstBytes, $magicNumbers)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Your archive does not seem to be a valid zip file.'),
                ], 422);
                return;
            }

            if (!file_put_contents($archivePath, $archiveContent)) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => t('api', 'Cannot write archive in the temporary location.'),
                ], 422);
                return;
            }

            $_FILES['archive'] = [
                'name'      => basename($archivePath),
                'type'      => 'application/zip',
                'tmp_name'  => $archivePath,
                'error'     => 0,
                'size'      => filesize($archivePath),
            ];

            $archiveTemplate = new CampaignEmailTemplateUpload('upload');
            $archiveTemplate->archive = CUploadedFile::getInstanceByName('archive');

            if (!$archiveTemplate->validate()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $archiveTemplate->shortErrors->getAll(),
                ], 422);
                return;
            }

            $template->content = 'DUMMY DATA, IF YOU SEE THIS, SOMETHING WENT WRONG FROM THE API CALL!';
        }

        if (empty($template->content) && !empty($tempContent)) {
            $template->content = (string)$tempContent;
            $archiveTemplate = null;
            unset($tempContent);
        }

        if (empty($template->content)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Please provide a template for your campaign.'),
            ], 422);
            return;
        }

        // since 1.3.4.8
        // delivery servers for this campaign - start
        $deliveryServers = [];
        if (isset($attributes['delivery_servers']) && $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes') {
            if (!is_array($attributes['delivery_servers'])) {
                $attributes['delivery_servers'] = explode(',', (string)$attributes['delivery_servers']);
            }
            $attributes['delivery_servers'] = array_map('trim', $attributes['delivery_servers']);
            $attributes['delivery_servers'] = array_map('intval', $attributes['delivery_servers']);
            $_deliveryServers = $customer->getAvailableDeliveryServers();
            $servers = [];
            foreach ($_deliveryServers as $srv) {
                $servers[] = (int)$srv->server_id;
            }
            foreach ($attributes['delivery_servers'] as $serverId) {
                if (in_array($serverId, $servers)) {
                    $deliveryServers[] = $serverId;
                }
            }
            unset($_deliveryServers, $servers);
        }
        // delivery servers for this campaign - end

        $transaction = db()->beginTransaction();
        try {
            if ($sendAt != $campaign->send_at) {
                // since the date is already in customer timezone we need to convert it back to utc
                $sourceTimeZone         = new DateTimeZone($campaign->customer->timezone);
                $destinationTimeZone    = new DateTimeZone(app()->getTimeZone());

                $dateTime = new DateTime((string)$campaign->send_at, $sourceTimeZone);
                $dateTime->setTimezone($destinationTimeZone);
                $campaign->send_at = $dateTime->format('Y-m-d H:i:s');
            }

            // since 2.0.29
            $allowedStatuses = [Campaign::STATUS_PENDING_SENDING, Campaign::STATUS_DRAFT, Campaign::STATUS_PAUSED];
            if (isset($attributes['status']) && in_array($attributes['status'], $allowedStatuses)) {
                $campaign->status = $attributes['status'];
            }

            if (!$campaign->save()) {
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $campaign->shortErrors->getAll(),
                ], 422);
                return;
            }

            if (!$campaignOption->save()) {
                $transaction->rollback();
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $campaignOption->shortErrors->getAll(),
                ], 422);
                return;
            }

            if (!empty($archiveTemplate)) {
                $archiveTemplate->customer_id = (int)user()->getId();
                $archiveTemplate->campaign    = $campaign;

                if (!$archiveTemplate->uploader->handleUpload()) {
                    $transaction->rollback();
                    $this->renderJson([
                        'status'    => 'error',
                        'error'     => $archiveTemplate->shortErrors->getAll(),
                    ], 422);
                    return;
                }

                $template->content  = (string)$archiveTemplate->content;
            }

            if (empty($template->plain_text) && $template->auto_plain_text == CampaignTemplate::TEXT_YES) {
                $template->plain_text = (string)CampaignHelper::htmlToText((string)$template->content);
            }

            if ($template->plain_text) {
                $template->plain_text = (string)ioFilter()->purify($template->plain_text);
            }

            $template->campaign_id = (int)$campaign->campaign_id;

            if (!$template->save()) {
                $transaction->rollback();
                $this->renderJson([
                    'status'    => 'error',
                    'error'     => $template->shortErrors->getAll(),
                ], 422);
                return;
            }

            // since 1.3.4.8
            if (!empty($deliveryServers)) {
                if (isset($attributes['delivery_servers']) && is_array($attributes['delivery_servers'])) {
                    CampaignToDeliveryServer::model()->deleteAllByAttributes([
                        'campaign_id' => (int)$campaign->campaign_id,
                    ]);
                }
                foreach ($deliveryServers as $serverId) {
                    $campaignToDeliveryServer = new CampaignToDeliveryServer();
                    $campaignToDeliveryServer->campaign_id = (int)$campaign->campaign_id;
                    $campaignToDeliveryServer->server_id   = (int)$serverId;
                    $campaignToDeliveryServer->save();
                }
            }

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            $this->renderJson([
                'status'    => 'error',
                'error'     => $e->getMessage(),
            ], 422);
            return;
        }

        $this->renderJson([
            'status'    => 'success',
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionCopy($campaign_uid)
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        /** @var Campaign|null $newCampaign */
        $newCampaign = $campaign->copy();

        if (empty($newCampaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Unable to copy the campaign.'),
            ], 400);
            return;
        }

        $this->renderJson([
            'status'       => 'success',
            'campaign_uid' => $newCampaign->campaign_uid,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionPause_unpause($campaign_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        $campaign->pauseUnpause();

        $this->renderJson([
            'status'   => 'success',
            'campaign' => [
                'status' => $campaign->status,
            ],
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionMark_sent($campaign_uid)
    {
        if (!request()->getIsPutRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only PUT requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        if (!$campaign->markAsSent()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not allow marking it as sent!'),
            ], 400);
            return;
        }

        $this->renderJson([
            'status'   => 'success',
            'campaign' => [
                'status' => $campaign->status,
            ],
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionDelete($campaign_uid)
    {
        if (!request()->getIsDeleteRequest()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'Only DELETE requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        if (!$campaign->getRemovable()) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'This campaign cannot be removed now.'),
            ], 400);
            return;
        }

        $campaign->delete();

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $campaign,
        ]));

        $this->renderJson([
            'status'    => 'success',
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionStats($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => Yii::t('api', 'The campaign does not exist.'),
            ], 404);
            return;
        }

        $stats = $campaign->getStats();
        $data  = [
            'campaign_status'           => (string)$campaign->status,
            'subscribers_count'         => $stats->getSubscribersCount(),
            'processed_count'           => $stats->getProcessedCount(),
            'delivery_success_count'    => $stats->getDeliverySuccessCount(),
            'delivery_success_rate'     => $stats->getDeliverySuccessRate(),
            'delivery_error_count'      => $stats->getDeliveryErrorCount(),
            'delivery_error_rate'       => $stats->getDeliveryErrorRate(),
            'opens_count'               => $stats->getOpensCount(),
            'opens_rate'                => $stats->getOpensRate(),
            'unique_opens_count'        => $stats->getUniqueOpensCount(),
            'unique_opens_rate'         => $stats->getUniqueOpensRate(),
            'clicks_count'              => $stats->getClicksCount(),
            'clicks_rate'               => $stats->getClicksRate(),
            'unique_clicks_count'       => $stats->getUniqueClicksCount(),
            'unique_clicks_rate'        => $stats->getUniqueClicksRate(),
            'unsubscribes_count'        => $stats->getUnsubscribesCount(),
            'unsubscribes_rate'         => $stats->getUnsubscribesRate(),
            'complaints_count'          => $stats->getComplaintsCount(),
            'complaints_rate'           => $stats->getComplaintsRate(),
            'bounces_count'             => $stats->getBouncesCount(),
            'bounces_rate'              => $stats->getBouncesRate(),
            'hard_bounces_count'        => $stats->getHardBouncesCount(),
            'hard_bounces_rate'         => $stats->getHardBouncesRate(),
            'soft_bounces_count'        => $stats->getSoftBouncesCount(),
            'soft_bounces_rate'         => $stats->getSoftBouncesRate(),
            'internal_bounces_count'    => $stats->getInternalBouncesCount(),
            'internal_bounces_rate'     => $stats->getInternalBouncesRate(),
        ];

        $this->renderJson([
            'status'    => 'success',
            'data'      => $data,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     */
    public function loadCampaignByUid(string $campaign_uid): ?Campaign
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        return $model;
    }

    /**
     * @param string $list_uid
     *
     * @return Lists|null
     */
    public function loadListByUid(string $list_uid): ?Lists
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        return Lists::model()->find($criteria);
    }

    /**
     * It will generate the timestamp that will be used to generate the ETAG for GET requests.
     *
     * @return int
     * @throws CException
     */
    public function generateLastModified()
    {
        static $lastModified;

        if ($lastModified !== null) {
            return $lastModified;
        }

        $row = [];

        if ($this->getAction()->getId() == 'index') {
            $perPage    = (int)request()->getQuery('per_page', 10);
            $page       = (int)request()->getQuery('page', 1);
            $maxPerPage = 50;
            $minPerPage = 10;

            if ($perPage < $minPerPage) {
                $perPage = $minPerPage;
            }

            if ($perPage > $maxPerPage) {
                $perPage = $maxPerPage;
            }

            if ($page < 1) {
                $page = 1;
            }

            $limit  = $perPage;
            $offset = ($page - 1) * $perPage;

            $sql = '
                SELECT AVG(t.last_updated) as `timestamp`
                FROM (
                    SELECT `a`.`customer_id`, UNIX_TIMESTAMP(`a`.`last_updated`) as `last_updated`
                    FROM `{{campaign}}` `a`
                    WHERE `a`.`customer_id` = :cid
                    ORDER BY a.`campaign_id` DESC
                    LIMIT :l OFFSET :o
                ) AS t
                WHERE `t`.`customer_id` = :cid
            ';

            $command = db()->createCommand($sql);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);
            $command->bindValue(':l', (int)$limit, PDO::PARAM_INT);
            $command->bindValue(':o', (int)$offset, PDO::PARAM_INT);

            $row = $command->queryRow();
        } elseif ($this->getAction()->getId() == 'view') {
            $sql = 'SELECT UNIX_TIMESTAMP(t.last_updated) as `timestamp` FROM `{{campaign}}` t WHERE `t`.`campaign_uid` = :uid AND `t`.`customer_id` = :cid LIMIT 1';
            $command = db()->createCommand($sql);
            $command->bindValue(':uid', request()->getQuery('campaign_uid'), PDO::PARAM_STR);
            $command->bindValue(':cid', (int)user()->getId(), PDO::PARAM_INT);

            $row = $command->queryRow();
        }

        if (isset($row['timestamp'])) {
            $timestamp = round((float)$row['timestamp']);
            if (preg_match('/\.(\d+)/', (string)$row['timestamp'], $matches)) {
                $timestamp += (int)$matches[1];
            }
            return $lastModified = (int)$timestamp;
        }

        return $lastModified = parent::generateLastModified();
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _setValidationRules(CEvent $event)
    {
        /** @var CList $rules */
        $rules = $event->params['rules'];
        $rules->clear();
        $rules->add(['name, from_name, from_email, subject, reply_to, to_name, send_at', 'required']);
        $rules->add(['name, to_name, subject', 'length', 'max'=>255]);
        $rules->add(['from_name, reply_to, from_email', 'length', 'max'=>100]);
        $rules->add(['reply_to, from_email', '_validateEMailWithTag']);
        $rules->add(['send_at', 'date', 'format' => 'yyyy-MM-dd HH:mm:ss']);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _beforeValidate(CEvent $event)
    {
        /** @var Campaign $campaign */
        $campaign   = $this->getData('campaign');
        $tags       = $campaign->getSubjectToNameAvailableTags();
        $attributes = ['subject', 'to_name'];

        foreach ($attributes as $attribute) {
            $content = html_decode($campaign->$attribute);
            foreach ($tags as $tag) {
                if (!isset($tag['tag']) || !isset($tag['required']) || !$tag['required']) {
                    continue;
                }
                if (!isset($tag['pattern']) && strpos($content, $tag['tag']) === false) {
                    $campaign->addError($attribute, t('lists', 'The following tag is required but was not found in your content: {tag}', [
                        '{tag}' => $tag['tag'],
                    ]));
                } elseif (isset($tag['pattern']) && !preg_match($tag['pattern'], $content)) {
                    $campaign->addError($attribute, t('lists', 'The following tag is required but was not found in your content: {tag}', [
                        '{tag}' => $tag['tag'],
                    ]));
                }
            }
        }
    }
}
