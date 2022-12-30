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
    public $campaignReportsController = 'campaigns_reports';

    /**
     * @var string
     */
    public $campaignReportsExportController = 'campaigns_reports_export';

    /**
     * @var bool
     */
    public $trackOpeningInternalCall = false;

    /**
     * Show the overview for a campaign, needs access and login
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOverview($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!empty($campaign->customer->language)) {
            app()->setLanguage($campaign->customer->language->getLanguageAndLocaleCode());
        }

        if ($campaign->shareReports->share_reports_enabled != CampaignOptionShareReports::TEXT_YES) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $session = session();
        if (!isset($session['campaign_reports_access_' . $campaign_uid])) {
            $this->redirect(['campaigns_reports/login', 'campaign_uid' => $campaign_uid]);
            return;
        }
        $this->addPageScript(['src' => AssetsUrl::js('campaigns.js')]);
        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaign overview'),
            'pageHeading'     => t('campaigns', 'Campaign overview'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns'),
                $campaign->name . ' ' => createUrl('campaigns/overview', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Overview'),
            ],
        ]);

        // render
        $this->render('customer.views.campaigns.overview', compact('campaign'));
    }

    /**
     * Will show the web version of a campaign email
     *
     * @param string $campaign_uid
     * @param mixed $subscriber_uid
     *
     * @return void
     * @throws Exception
     */
    public function actionWeb_version($campaign_uid, $subscriber_uid = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->find($criteria);

        if (empty($campaign)) {
            $this->redirect(['site/index']);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = null;
        if (!empty($subscriber_uid)) {
            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByUid((string)$subscriber_uid);
        }

        $list     = $campaign->list;
        $customer = $list->customer;

        /** @var CampaignTemplate|null $template */
        $template = $campaign->template;
        if (empty($template)) {
            $this->redirect(['site/index']);
            return;
        }

        $emailContent = $template->content;
        if (empty($emailContent)) {
            $this->redirect(['site/index']);
            return;
        }

        $emailHeader  = null;
        $emailFooter  = null;

        // 1.5.5
        if ($campaign->template->only_plain_text == CampaignTemplate::TEXT_YES) {
            $emailContent = nl2br($emailContent);
        }

        if (!empty($campaign->option) && !empty($campaign->option->preheader)) {
            $emailContent = CampaignHelper::injectPreheader($emailContent, $campaign->option->preheader, $campaign);
        }

        if (($emailHeader = (string)$customer->getGroupOption('campaigns.email_header', '')) && strlen(trim($emailHeader)) > 5) {
            $emailContent = CampaignHelper::injectEmailHeader($emailContent, $emailHeader, $campaign);
        }

        if (($emailFooter = (string)$customer->getGroupOption('campaigns.email_footer', '')) && strlen(trim($emailFooter)) > 5) {
            $emailContent = CampaignHelper::injectEmailFooter($emailContent, $emailFooter, $campaign);
        }

        if (CampaignHelper::contentHasXmlFeed($emailContent)) {
            $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, true);
        }

        if (CampaignHelper::contentHasJsonFeed($emailContent)) {
            $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, true);
        }

        // 1.5.3
        if (CampaignHelper::hasRemoteContentTag($emailContent)) {
            $emailContent = CampaignHelper::fetchContentForRemoteContentTag($emailContent, $campaign, $subscriber);
        }
        //

        if ($subscriber) {
            if (!$campaign->getIsDraft() && !empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $campaign, $subscriber);
            }
        } else {
            $subscriber = new ListSubscriber();
        }

        $emailData = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, true);
        [, , $emailContent] = $emailData;

        // since 2.0.32
        // This is a special case, where the custom fields can contain URLs and if we do not do the below
        // then those URLs will not be parsed into tracking URLs.
        // More details at https://github.com/onetwist-software/mailwizz/issues/653
        if (
            !$campaign->getIsDraft() &&
            !empty($campaign->option) &&
            $campaign->option->url_tracking == CampaignOption::TEXT_YES &&
            !empty($subscriber->subscriber_id) &&
            CampaignHelper::contentHasUntransformedLinksForTracking($emailContent, $campaign, $subscriber)
        ) {
            $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $campaign, $subscriber, true);

            $emailData = CampaignHelper::parseContent($emailContent, $campaign, $subscriber);
            [, , $emailContent] = $emailData;
        }

        // 1.5.3
        if (!empty($emailContent) && CampaignHelper::isTemplateEngineEnabled()) {
            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailContent, $campaign, $subscriber);
            $emailContent  = CampaignHelper::parseByTemplateEngine($emailContent, $searchReplace);
        }
        //

        // 1.4.5
        $emailContent = hooks()->applyFilters('frontend_campaigns_controller_web_version_action_email_content', $emailContent, $list, $customer, $template, $campaign, $subscriber);

        echo (string)$emailContent;
    }

    /**
     * Will track and register the email openings
     *
     * GMail will store the email images, therefore there might be cases when successive opens by same subscriber
     * will not be tracked.
     * In order to trick this, it seems that the content length must be set to 0 as pointed out here:
     * http://www.emailmarketingtipps.de/2013/12/07/gmails-image-caching-affects-email-marketing-heal-opens-tracking/
     *
     * Note: When mod gzip enabled on server, the content length will be at least 20 bytes as explained in this bug:
     * https://issues.apache.org/bugzilla/show_bug.cgi?id=51350
     * In order to alleviate this, seems that we need to use a fake content type, like application/json
     *
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     */
    public function actionTrack_opening($campaign_uid, $subscriber_uid)
    {
        if (!$this->trackOpeningInternalCall) {
            header('Content-Type: application/json');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: private');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('P3P: CP="OTI DSP COR CUR IVD CONi OTPi OUR IND UNI STA PRE"');
            header('Pragma: no-cache');
            header('Content-Length: 0');
        }

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
        $campaign = Campaign::model()->find($criteria);

        if (empty($campaign)) {
            if ($this->trackOpeningInternalCall) {
                return;
            }
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByUid($subscriber_uid);
        if (empty($subscriber)) {
            if ($this->trackOpeningInternalCall) {
                return;
            }
            app()->end();
            return;
        }

        // since 1.9.5
        $mutexKey = sha1(sprintf('%s:%s:%s:%s', __METHOD__, $campaign_uid, $subscriber_uid, date('YmdH')));
        if (!mutex()->acquire($mutexKey, 30)) {
            if ($this->trackOpeningInternalCall) {
                return;
            }
            app()->end();
            return;
        }

        // since 1.3.5.8
        hooks()->addFilter('frontend_campaigns_can_track_opening', [$this, '_actionCanTrackOpening']);
        $canTrack = hooks()->applyFilters('frontend_campaigns_can_track_opening', true, $this, $campaign);

        if ($canTrack) {
            // only allow confirmed and moved subs
            $canTrack = $subscriber->getIsConfirmed() || $subscriber->getIsMoved();
        }

        if (!$canTrack) {

            // 1.9.5 - relase the mutex
            mutex()->release($mutexKey);

            if ($this->trackOpeningInternalCall) {
                return;
            }

            app()->end();
            return;
        }

        // 1.5.2 - update ip address if changed
        if (($ipAddress = (string)request()->getUserHostAddress()) && FilterVarHelper::ip($ipAddress)) {
            $subscriber->saveIpAddress($ipAddress);
        }

        hooks()->addAction('frontend_campaigns_after_track_opening', [$this, '_openActionChangeSubscriberListField'], 99);
        hooks()->addAction('frontend_campaigns_after_track_opening', [$this, '_openActionAgainstSubscriber'], 100);

        // since 1.6.8
        hooks()->addAction('frontend_campaigns_after_track_opening', [$this, '_openCreateWebhookRequest'], 101);

        // since 2.0.29
        if ($campaign->getCanDoAbTest()) {
            hooks()->addAction('frontend_campaigns_after_track_opening', [$this, '_openUpdateAbTestSubjectOpensCountWithSubscriber'], 102);
        }

        $track = new CampaignTrackOpen();
        $track->campaign_id     = (int)$campaign->campaign_id;
        $track->subscriber_id   = (int)$subscriber->subscriber_id;
        $track->ip_address      = (string)request()->getUserHostAddress();
        $track->user_agent      = substr((string)request()->getUserAgent(), 0, 255);

        if ($track->save(false)) {
            // raise the action, hook added in 1.2
            $this->setData('ipLocationSaved', false);
            try {
                hooks()->doAction('frontend_campaigns_after_track_opening', $this, $track, $campaign, $subscriber);
            } catch (Exception $e) {
            }
        }

        // 1.9.5 - relase the mutex
        mutex()->release($mutexKey);

        if ($this->trackOpeningInternalCall) {
            return;
        }

        app()->end();
    }

    /**
     * Will track the clicks the subscribers made in the campaign email
     *
     * @param string $campaign_uid
     * @param string $subscriber_uid
     * @param string $hash
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionTrack_url($campaign_uid, $subscriber_uid, $hash)
    {
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->find($criteria);

        if (empty($campaign)) {
            hooks()->doAction('frontend_campaigns_track_url_item_not_found', [
                'step' => 'campaign',
            ]);
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $subscriber = ListSubscriber::model()->findByUid($subscriber_uid);
        if (empty($subscriber)) {
            hooks()->doAction('frontend_campaigns_track_url_item_not_found', [
                'step' => 'subscriber',
            ]);
            if ($redirect = $campaign->list->getSubscriber404Redirect()) {
                $this->redirect($redirect);
                return;
            }
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // since 1.9.5
        $mutexKey = sha1(sprintf('%s:%s:%s:%s', __METHOD__, $campaign_uid, $subscriber_uid, date('YmdH')));
        if (!mutex()->acquire($mutexKey, 30)) {
            app()->end();
            return;
        }

        // 1.5.2 - update ip address if changed
        if (($ipAddress = (string)request()->getUserHostAddress()) && FilterVarHelper::ip($ipAddress)) {
            $subscriber->saveIpAddress($ipAddress);
        }

        // since 1.4.2
        $hash = str_replace(['.', ' ', '-', '_', '='], '', $hash);
        $hash = substr($hash, 0, 40);
        //

        $url = CampaignUrl::model()->findByAttributes([
            'campaign_id'   => $campaign->campaign_id,
            'hash'          => $hash,
        ]);

        if (empty($url)) {

            // 1.9.5 - relase the mutex
            mutex()->release($mutexKey);

            hooks()->doAction('frontend_campaigns_track_url_item_not_found', [
                'step' => 'url',
            ]);

            if ($redirect = $campaign->list->getSubscriber404Redirect()) {
                $this->redirect($redirect);
                return;
            }

            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // since 1.3.5.8
        hooks()->addFilter('frontend_campaigns_can_track_url', [$this, '_actionCanTrackUrl']);
        $canTrack = hooks()->applyFilters('frontend_campaigns_can_track_url', true, $this, $campaign, $subscriber, $url);

        if ($canTrack) {
            // only allow confirmed and moved subs
            $canTrack = $subscriber->getIsConfirmed() || $subscriber->getIsMoved();
        }

        if (!$canTrack) {

            // since 1.3.8.8
            $url->destination = StringHelper::normalizeUrl($url->destination);
            hooks()->doAction('frontend_campaigns_after_track_url_before_redirect', $this, $campaign, $subscriber, $url);
            $destination = $url->destination;

            if (preg_match('/\[(.*)?\]/', $destination)) {
                [, , $destination] = CampaignHelper::parseContent($destination, $campaign, $subscriber);
            }

            // since 1.7.6
            if (!empty($destination) && CampaignHelper::isTemplateEngineEnabled()) {
                $searchReplace = CampaignHelper::getCommonTagsSearchReplace($destination, $campaign, $subscriber);
                $destination   = CampaignHelper::parseByTemplateEngine($destination, $searchReplace);
            }
            //

            // 1.9.5 - relase the mutex
            mutex()->release($mutexKey);

            // since 2.1.4
            if (empty($destination) || !FilterVarHelper::urlAnyScheme($destination)) {
                throw new CHttpException(404, t('app', 'The requested page does not exist.'));
            }

            $this->redirect($destination, true, 301);
            return;
            //
        }

        // 1.6.8
        hooks()->addAction('frontend_campaigns_after_track_url', [$this, '_urlCreateWebhookRequest'], 100);

        hooks()->addAction('frontend_campaigns_after_track_url_before_redirect', [$this, '_urlActionChangeSubscriberListField'], 99);
        hooks()->addAction('frontend_campaigns_after_track_url_before_redirect', [$this, '_urlActionAgainstSubscriber'], 100);

        $track = new CampaignTrackUrl();
        $track->url_id          = (int)$url->url_id;
        $track->subscriber_id   = (int)$subscriber->subscriber_id;
        $track->ip_address      = (string)request()->getUserHostAddress();
        $track->user_agent      = substr((string)request()->getUserAgent(), 0, 255);

        try {
            if ($track->save(false)) {
                // hook added in 1.2
                $this->setData('ipLocationSaved', false);
                try {
                    hooks()->doAction('frontend_campaigns_after_track_url', $this, $track, $campaign, $subscriber);
                } catch (Exception $e) {
                }
            }
        } catch (Exception $e) {
        }

        // changed since 1.3.5.9
        $url->destination = StringHelper::normalizeUrl($url->destination);
        hooks()->doAction('frontend_campaigns_after_track_url_before_redirect', $this, $campaign, $subscriber, $url);

        $destination = $url->destination;
        $destination = StringHelper::normalizeUrl($destination);

        if (preg_match('/\[(.*)?\]/', $destination)) {
            $server = null;

            // since 1.5.2
            if (strpos($destination, '[DS_') !== false) {
                $log = CampaignDeliveryLog::model()->findByAttributes([
                    'campaign_id'   => $campaign->campaign_id,
                    'subscriber_id' => $subscriber->subscriber_id,
                ]);
                if (!empty($log) && !empty($log->server_id) && !empty($log->server)) {
                    $server = $log->server;
                }
            }
            //

            [, , $destination]  = CampaignHelper::parseContent($destination, $campaign, $subscriber, false, $server);
            $destination        = StringHelper::normalizeUrl($destination);
        }

        // since 1.7.6
        if (!empty($destination) && CampaignHelper::isTemplateEngineEnabled()) {
            $searchReplace = CampaignHelper::getCommonTagsSearchReplace($destination, $campaign, $subscriber);
            $destination   = CampaignHelper::parseByTemplateEngine($destination, $searchReplace);
            $destination   = StringHelper::normalizeUrl($destination);
        }
        //

        // since 1.3.5.9
        if ($campaign->option->open_tracking == CampaignOption::TEXT_YES && !$subscriber->hasOpenedCampaign($campaign)) {
            $this->trackOpeningInternalCall = true;
            $this->actionTrack_opening($campaign->campaign_uid, $subscriber->subscriber_uid);
            $this->trackOpeningInternalCall = false;
        }
        //

        // 1.9.5 - relase the mutex
        mutex()->release($mutexKey);

        // since 2.1.4
        if (empty($destination) || !FilterVarHelper::urlAnyScheme($destination)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $this->redirect($destination, true, 301);
    }

    /**
     * @param string $campaign_uid
     * @param mixed $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionForward_friend($campaign_uid, $subscriber_uid = null)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->find($criteria);

        if (empty($campaign)) {
            $this->redirect(['site/index']);
            return;
        }

        $subscriber = null;
        if (!empty($subscriber_uid)) {
            $subscriber = ListSubscriber::model()->findByUid((string)$subscriber_uid);
            if (empty($subscriber)) {
                $this->redirect(['site/index']);
                return;
            }
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $forward     = new CampaignForwardFriend();
        $forwardUrl  = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid);

        if (!empty($subscriber)) {
            $forward->from_email = $subscriber->email;
        }
        $forward->subject = $campaign->option->getForwardFriendSubject();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($forward->getModelName(), []))) {
            $forward->attributes    = $attributes;
            $forward->campaign_id   = (int)$campaign->campaign_id;
            $forward->subscriber_id = $subscriber ? (int)$subscriber->subscriber_id : null;
            $forward->ip_address    = (string)request()->getUserHostAddress();
            $forward->user_agent    = substr((string)request()->getUserAgent(), 0, 255);

            $forwardsbyIp = CampaignForwardFriend::model()->countByAttributes([
                'campaign_id' => $forward->campaign_id,
                'ip_address'  => $forward->ip_address,
            ]);

            $forwardLimit = 10;
            if ($forwardsbyIp >= $forwardLimit) {
                notify()->addError(t('campaigns', 'You can only forward a campaign {num} times!', ['{num}' => $forwardLimit]));
                $this->refresh();
            }

            if (!$forward->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                $message = '';
                if (!empty($forward->message)) {
                    $message .= t('campaigns', '{from_name} also left this message for you:', [
                        '{from_name}' => $forward->from_name,
                    ]);
                    $message .= ' <br />' . $forward->message;
                }

                $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                    'forward-campaign-friend',
                    [
                        'subject' => $forward->subject,
                    ],
                    [
                        '[TO_NAME]'     => $forward->to_name,
                        '[FROM_NAME]'   => $forward->from_name,
                        '[MESSAGE]'     => $message,
                        '[CAMPAIGN_URL]'=> $forwardUrl,
                    ]
                );

                $email = new TransactionalEmail();
                $email->customer_id = (int)$campaign->customer_id;
                $email->to_name     = $forward->to_name;
                $email->to_email    = $forward->to_email;
                $email->from_name   = $forward->from_name;
                $email->from_email  = $forward->from_email;
                $email->subject     = $forward->subject;
                $email->body        = $params['body'];
                $email->save();

                notify()->addSuccess(t('campaigns', 'Your message has been successfully forwarded!'));
                $this->refresh();
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Forward to a friend'),
            'pageHeading'     => t('campaigns', 'Forward to a friend'),
            'pageBreadcrumbs' => [],
        ]);

        $this->render('forward-friend', compact('campaign', 'subscriber', 'forward', 'forwardUrl'));
    }

    /**
     * Will record the abuse report for a campaign
     *
     * @param string $campaign_uid
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws Exception
     */
    public function actionReport_abuse($campaign_uid, $list_uid, $subscriber_uid)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->find($criteria);

        if (empty($campaign)) {
            $this->redirect(['site/index']);
            return;
        }

        /** @var Lists|null $list */
        $list = Lists::model()->findByUid($list_uid);
        if (empty($list)) {
            $this->redirect(['site/index']);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'subscriber_uid' => $subscriber_uid,
        ]);

        if (empty($subscriber)) {
            $this->redirect(['site/index']);
            return;
        }

        if (!$subscriber->getIsConfirmed() && !$subscriber->getIsMoved()) {
            $this->redirect(['site/index']);
            return;
        }

        /** @var OptionCommon $common */
        $common  = container()->get(OptionCommon::class);
        $report  = new CampaignAbuseReport();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($report->getModelName(), []))) {
            $report->attributes      = $attributes;
            $report->customer_id     = (int)$list->customer_id;
            $report->campaign_id     = (int)$campaign->campaign_id;
            $report->list_id         = (int)$list->list_id;
            $report->subscriber_id   = (int)$subscriber->subscriber_id;
            $report->customer_info   = sprintf('%s(%s)', $list->customer->getFullName(), $list->customer->email);
            $report->campaign_info   = $campaign->name;
            $report->list_info       = sprintf('%s(%s)', $list->name, $list->display_name);
            $report->subscriber_info = $subscriber->email;
            $report->ip_address      = (string)request()->getUserHostAddress();
            $report->user_agent      = StringHelper::truncateLength((string)request()->getUserAgent(), 255);

            if ($report->save()) {
                $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);

                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = (int)$campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = (int)$subscriber->subscriber_id;
                $trackUnsubscribe->note          = 'Abuse complaint!';
                $trackUnsubscribe->save(false);

                // since 1.5.2 - start notifications
                $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                    'new-abuse-report',
                    [
                        'subject' => t('campaigns', 'New abuse report!'),
                    ],
                    $searchReplace = [
                        '[CUSTOMER_NAME]'       => $campaign->customer->getFullName(),
                        '[CAMPAIGN_NAME]'       => $campaign->name,
                        '[ABUSE_REPORTS_URL]'   => apps()->getAppUrl('customer', sprintf('campaigns/%s/reports/abuse-reports', $campaign->campaign_uid), true),
                    ]
                );

                // 1.9.23
                if ($campaign->customer->getGroupOption('campaigns.abuse_reports_email_notification', 'yes') == 'yes') {
                    $email = new TransactionalEmail();
                    if ($email->hasAttribute('fallback_system_servers')) {
                        $email->fallback_system_servers = TransactionalEmail::TEXT_YES;
                    }
                    $email->customer_id             = (int)$campaign->customer_id;
                    $email->to_name                 = $campaign->customer->getFullName();
                    $email->to_email                = $campaign->customer->email;
                    $email->from_name               = $common->getSiteName();
                    $email->subject                 = $params['subject'];
                    $email->body                    = $params['body'];
                    $email->save();
                }

                $message = new CustomerMessage();
                $message->customer_id = $campaign->customer->customer_id;
                $message->title       = 'New abuse report!';
                $message->message     = 'A new abuse report has been created for the campaign "{campaign_name}". Please visit the "<a href="{abuse_reports_url}">Abuse Reports</a>" area to handle it!';
                $message->message_translation_params = [
                    '{campaign_name}'       => $searchReplace['[CAMPAIGN_NAME]'],
                    '{abuse_reports_url}'   => $searchReplace['[ABUSE_REPORTS_URL]'],
                ];
                $message->save();
                //

                notify()->addSuccess(t('campaigns', 'Thank you for your report, we will take proper actions against this as soon as possible!'));
            } else {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Report abuse'),
            'pageHeading'     => t('campaigns', 'Report abuse'),
            'pageBreadcrumbs' => [],
        ]);

        $this->render('report-abuse', compact('report'));
    }

    /**
     * @since 1.7.6
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionVcard($campaign_uid)
    {
        $campaign = $this->loadCampaignModel($campaign_uid);
        $customer = $campaign->customer;
        $list     = $campaign->list;
        $company  = $list->company;

        if (!empty($customer->language_id)) {
            app()->setLanguage($customer->language->getLanguageAndLocaleCode());
        }

        $vcard = new JeroenDesloovere\VCard\VCard();

        $vcard->addName($campaign->from_name);
        $vcard->addCompany($company->name);
        $vcard->addEmail($campaign->from_email);

        if (!empty($company->phone)) {
            $vcard->addPhoneNumber($company->phone, 'PREF;WORK');
        }

        $zone = !empty($company->zone_id) ? $company->zone->name : $company->zone_name;
        $vcard->addAddress(null, null, $company->address_1, $company->city, $zone, $company->zip_code, $company->country->name);

        if (!empty($company->website)) {
            $vcard->addURL($company->website);
        }

        $vcard->download();
    }

    /**
     * @param Controller $controller
     * @param CampaignTrackOpen $track
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return void
     * @throws CException
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function _openActionChangeSubscriberListField(Controller $controller, CampaignTrackOpen $track, Campaign $campaign, ListSubscriber $subscriber)
    {
        $models = CampaignOpenActionListField::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            $valueModel = ListFieldValue::model()->findByAttributes([
                'field_id'      => $model->field_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ]);
            if (empty($valueModel)) {
                $valueModel = new ListFieldValue();
                $valueModel->field_id       = (int)$model->field_id;
                $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
            }

            $valueModel->value = $model->getParsedFieldValueByListFieldValue(new CAttributeCollection([
                'valueModel' => $valueModel,
                'campaign'   => $campaign,
                'subscriber' => $subscriber,
                'trackOpen'  => $track,
                'event'      => 'campaign:subscriber:track:open',
            ]));
            $valueModel->save();
        }
    }

    /**
     * @param Controller $controller
     * @param CampaignTrackOpen $track
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function _openActionAgainstSubscriber(Controller $controller, CampaignTrackOpen $track, Campaign $campaign, ListSubscriber $subscriber)
    {
        $models = CampaignOpenActionSubscriber::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            if ($model->action == CampaignOpenActionSubscriber::ACTION_MOVE) {
                $subscriber->moveToList((int)$model->list_id, false, true);
            } else {
                $subscriber->copyToList((int)$model->list_id, false, true);
            }
        }
    }

    /**
     * @param Controller $controller
     * @param CampaignTrackOpen $track
     * @param Campaign $campaign
     *
     * @return void
     * @since 1.6.8
     */
    public function _openCreateWebhookRequest(Controller $controller, CampaignTrackOpen $track, Campaign $campaign)
    {
        $models = CampaignTrackOpenWebhook::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            $request = new CampaignTrackOpenWebhookQueue();
            $request->webhook_id    = (int)$model->webhook_id;
            $request->track_open_id = $track->id;
            $request->save(false);
        }
    }

    /**
     * @param Controller $controller
     * @param CampaignTrackOpen $track
     * @param Campaign $campaign
     *
     * @return void
     * @since 2.0.29
     */
    public function _openUpdateAbTestSubjectOpensCountWithSubscriber(Controller $controller, CampaignTrackOpen $track, Campaign $campaign)
    {
        $campaign->updateAbTestSubjectOpensCountFromTrackOpen($track);
    }

    /**
     * @param Controller $controller
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @param CampaignUrl $url
     *
     * @return void
     * @throws CException
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function _urlActionChangeSubscriberListField(Controller $controller, Campaign $campaign, ListSubscriber $subscriber, CampaignUrl $url)
    {
        $models = CampaignTemplateUrlActionListField::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
            'url'         => $url->destination,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            $valueModel = ListFieldValue::model()->findByAttributes([
                'field_id'      => (int)$model->field_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);
            if (empty($valueModel)) {
                $valueModel = new ListFieldValue();
                $valueModel->field_id       = (int)$model->field_id;
                $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
            }

            $valueModel->value = $model->getParsedFieldValueByListFieldValue(new CAttributeCollection([
                'valueModel' => $valueModel,
                'campaign'   => $campaign,
                'subscriber' => $subscriber,
                'url'        => $url,
                'event'      => 'campaign:subscriber:track:click',
            ]));
            $valueModel->save();
        }
    }

    /**
     * @param Controller $controller
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @param CampaignUrl $url
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function _urlActionAgainstSubscriber(Controller $controller, Campaign $campaign, ListSubscriber $subscriber, CampaignUrl $url)
    {
        $models = CampaignTemplateUrlActionSubscriber::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
            'url'         => $url->destination,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            if ($model->action == CampaignOpenActionSubscriber::ACTION_MOVE) {
                $subscriber->moveToList((int)$model->list_id, false, true);
            } else {
                $subscriber->copyToList((int)$model->list_id, false, true);
            }
        }
    }

    /**
     * @param Controller $controller
     * @param CampaignTrackUrl $track
     * @param Campaign $campaign
     *
     * @return void
     * @since 1.6.8
     */
    public function _urlCreateWebhookRequest(Controller $controller, CampaignTrackUrl $track, Campaign $campaign)
    {
        $models = CampaignTrackUrlWebhook::model()->findAllByAttributes([
            'campaign_id'       => (int)$campaign->campaign_id,
            'track_url_hash'    => $track->url->hash,
        ]);

        if (empty($models)) {
            return;
        }

        foreach ($models as $model) {
            $request = new CampaignTrackUrlWebhookQueue();
            $request->webhook_id    = (int)$model->webhook_id;
            $request->track_url_id  = (int)$track->id;
            $request->save(false);
        }
    }

    /**
     * @param bool $canTrack
     *
     * @return bool
     */
    public function _actionCanTrackOpening($canTrack)
    {
        if (!$canTrack) {
            return $canTrack;
        }

        /** @var OptionCampaignExcludeIpsFromTracking $model */
        $model = container()->get(OptionCampaignExcludeIpsFromTracking::class);
        return $model->canTrackIpAction((string)request()->getUserHostAddress());
    }

    /**
     * @param bool $canTrack
     *
     * @return bool
     */
    public function _actionCanTrackUrl($canTrack)
    {
        if (!$canTrack) {
            return $canTrack;
        }

        /** @var OptionCampaignExcludeIpsFromTracking $model */
        $model = container()->get(OptionCampaignExcludeIpsFromTracking::class);
        return $model->canTrackIpAction((string)request()->getUserHostAddress(), OptionCampaignExcludeIpsFromTracking::ACTION_CLICK);
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign
     * @throws CHttpException
     */
    public function loadCampaignModel(string $campaign_uid): Campaign
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.campaign_uid', $campaign_uid);
        $statuses = [
            Campaign::STATUS_DRAFT, Campaign::STATUS_PENDING_DELETE, Campaign::STATUS_PENDING_SENDING,
        ];
        $criteria->addNotInCondition('t.status', $statuses);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
