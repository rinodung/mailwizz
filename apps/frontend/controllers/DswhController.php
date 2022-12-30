<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DswhController
 *
 * Delivery Servers Web Hooks (DSWH) handler
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 */

class DswhController extends Controller
{
    /**
     * @var string
     */
    protected $pmtaMessageIdHeaderKey = 'header_Message-Id';

    /**
     * @param int $id
     *
     * @return void
     */
    public function actionIndex($id)
    {
        $server = DeliveryServer::model()->findByPk((int)$id);

        if (empty($server)) {
            app()->end();
            return;
        }

        $map = [
            'amazon-ses-web-api'   => [$this, 'processAmazonSes'],
            'mailgun-web-api'      => [$this, 'processMailgun'],
            'sendgrid-web-api'     => [$this, 'processSendgrid'],
            'elasticemail-web-api' => [$this, 'processElasticemail'],
            'dyn-web-api'          => [$this, 'processDyn'],
            'sparkpost-web-api'    => [$this, 'processSparkpost'],
            'mailjet-web-api'      => [$this, 'processMailjet'],
            'sendinblue-web-api'   => [$this, 'processSendinblue'],
            'tipimail-web-api'     => [$this, 'processTipimail'],
            'pepipost-web-api'     => [$this, 'processPepipost'],
            'postmark-web-api'     => [$this, 'processPostmark'],
        ];

        $map = (array)hooks()->applyFilters('dswh_process_map', $map, $server, $this);
        if (isset($map[$server->type]) && is_callable($map[$server->type])) {
            call_user_func_array($map[$server->type], [$server, $this]);
        }

        app()->end();
    }

    /**
     * Process DRH's GreenArrow
     *
     * @return void
     * @throws CException
     */
    public function actionDrh()
    {
        if (!count((array)request()->getPost('', []))) {
            app()->end();
            return;
        }

        $event = (string)request()->getPost('event_type', '');

        // header name: X-GreenArrow-Click-Tracking-ID
        // header value: [CAMPAIGN_UID]|[SUBSCRIBER_UID]
        $cs = (array)explode('|', (string)request()->getPost('click_tracking_id', ''));

        if (empty($event) || count($cs) != 2) {
            $this->end();
            return;
        }

        [$campaignUid, $subscriberUid] = $cs;

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByAttributes([
            'campaign_uid' => $campaignUid,
        ]);
        if (empty($campaign)) {
            $this->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $subscriberUid,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            $this->end();
            return;
        }

        if (stripos($event, 'bounce') !== false) {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                $this->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
            $bounceLog->message       = (string)request()->getPost('bounce_text', '');
            $bounceLog->bounce_type   = request()->getPost('bounce_type') == 'h' ? CampaignBounceLog::BOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT;
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist((string)$bounceLog->message);
            }

            $this->end();
            return;
        }

        if ($event == 'scomp') {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            $this->end();
            return;
        }

        if ($event == 'engine_unsub') {
            $subscriber->unsubscribeByCampaign($campaign);
            $this->end();
            return;
        }

        $this->end();
    }

    /**
     * Process Postal
     *
     * @return void
     * @throws CException
     */
    public function actionPostal()
    {
        $event = (string)file_get_contents('php://input');
        if (empty($event)) {
            app()->end();
            return;
        }
        $event = json_decode($event, true);

        if (empty($event) || !is_array($event)) {
            $event = [];
        }

        if (in_array($event['event'], ['MessageDeliveryFailed', 'MessageDelayed', 'MessageHeld'])) {
            $possibleMessageIds = array_filter([
                $event['payload']['message']['token'] ?? '',
                $event['payload']['message']['message_id'] ?? '',
            ]);

            if (empty($possibleMessageIds)) {
                app()->end();
                return;
            }

            $criteria = new CDbCriteria();
            $criteria->compare('status', CampaignDeliveryLog::STATUS_SUCCESS);
            $criteria->addInCondition('email_message_id', $possibleMessageIds);

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
            }

            if (empty($deliveryLog)) {
                app()->end();
                return;
            }

            $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
            if (empty($campaign)) {
                app()->end();
                return;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_id'    => $deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                app()->end();
                return;
            }

            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $message    = $event['payload']['details'] ?? 'BOUNCED BACK';
            $bounceType = CampaignBounceLog::BOUNCE_INTERNAL;

            if (!empty($event['payload']['status'])) {
                if (stripos($event['payload']['status'], 'hard') !== false) {
                    $bounceType = CampaignBounceLog::BOUNCE_HARD;
                } elseif (stripos($event['payload']['status'], 'soft') !== false) {
                    $bounceType = CampaignBounceLog::BOUNCE_SOFT;
                }
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = $message;
            $bounceLog->bounce_type     = $bounceType;
            $bounceLog->save();

            if ($bounceLog->bounce_type === CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }

            app()->end();
            return;
        }

        if ($event['event'] === 'MessageBounced') {
            $possibleMessageIds = array_filter([
                $event['payload']['original_message']['token'] ?? '',
                $event['payload']['original_message']['message_id'] ?? '',
            ]);

            if (empty($possibleMessageIds)) {
                app()->end();
                return;
            }

            $criteria = new CDbCriteria();
            $criteria->compare('status', CampaignDeliveryLog::STATUS_SUCCESS);
            $criteria->addInCondition('email_message_id', $possibleMessageIds);

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
            }

            if (empty($deliveryLog)) {
                app()->end();
                return;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
            if (empty($campaign)) {
                app()->end();
                return;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_id'    => $deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                app()->end();
                return;
            }

            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            // it still unclear how we should handle these
            // https://github.com/atech/postal/issues/253
            $message    = 'BOUNCED BACK';
            $bounceType = CampaignBounceLog::BOUNCE_INTERNAL;

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = $message;
            $bounceLog->bounce_type     = $bounceType;
            $bounceLog->save();

            app()->end();
        }
    }

    /**
     * Process Postmastery
     *
     * @return void
     * @throws CException
     */
    public function actionPostmastery()
    {
        $this->actionPmta();
    }

    /**
     * Process Inboxroad
     *
     * @return void
     * @throws CException
     */
    public function actionInboxroad()
    {
        // $this->pmtaMessageIdHeaderKey = 'header_message_IR-ID';
        $this->actionPmta();
    }

    /**
     * Process NewsMan
     *
     * @return void
     * @throws CException
     */
    public function actionNewsman()
    {
        $events = (string)request()->getPost('newsman_events', '');
        if (empty($events)) {
            app()->end();
            return;
        }
        $events = (array)json_decode($events, true);

        if (empty($events) || !is_array($events)) {
            $events = [];
        }

        foreach ($events as $event) {
            $messageId = $event['data']['send_id'] ?? '';
            if (empty($messageId)) {
                continue;
            }

            $criteria = new CDbCriteria();
            $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
            $criteria->params = [
                'email_message_id' => (string)$messageId,
                'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
            ];

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
            }

            if (empty($deliveryLog)) {
                continue;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => (int)$campaign->list_id,
                'subscriber_id'    => (int)$deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            if (in_array($event['type'], ['spam'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
                continue;
            }

            if (in_array($event['type'], ['unsub'])) {
                $subscriber->unsubscribeByCampaign($campaign);
                continue;
            }

            if (in_array($event['type'], ['bounce', 'reject'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id   = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
                $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_INTERNAL;
                $bounceLog->message       = $event['data']['meta']['subject'] ?? 'BOUNCED BACK';

                if ($event['type'] == 'reject') {
                    $bounceLog->save();
                    continue;
                }

                if (strpos($event['data']['meta']['reason'], 'soft') !== false) {
                    $bounceLog->bounce_type = CampaignBounceLog::BOUNCE_SOFT;
                } else {
                    $bounceLog->bounce_type = CampaignBounceLog::BOUNCE_HARD;
                }

                $bounceLog->save();

                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }
        }
    }

    /**
     * Process PMTA logs
     *
     * PMTA specific - 12.1.2.1 JSON
     * Each record will be a JSON object containing fields for all the configured fields for that record type
     * (even if there is no value for that field). There will be no header line.
     * The resulting accounting file/stream/content will be in NDJSON (newline-delimited JSON) format.
     *
     * BC specific - We also need to watch out for when posting arrays.
     *
     * @return void
     * @throws CException
     */
    public function actionPmta()
    {
        $postedEvents = (string)file_get_contents('php://input');
        if (empty($postedEvents)) {
            app()->end();
            return;
        }

        $parsedEvents = (array)json_decode($postedEvents, true);
        // in case this is just the top level event and not an array of event arrays
        if (!empty($parsedEvents) && array_key_exists('type', $parsedEvents)) {
            $parsedEvents = [$parsedEvents];
        }

        $events = collect([])
            // assume posting an array of events
            ->merge($parsedEvents)
            // assume ndjson
            ->merge(
                collect(explode(PHP_EOL, $postedEvents))->filter(function ($line) {
                    return !empty($line) && stripos($line, 'type') !== false && stripos($line, $this->pmtaMessageIdHeaderKey) !== false;
                })->map(function ($line) {
                    return (array)json_decode($line, true);
                })->filter(function ($event) {
                    return !empty($event) && is_array($event) && !empty($event['type']) && !empty($event[$this->pmtaMessageIdHeaderKey]);
                })->all()
            // remove anything that's not compliant
            )->filter(function ($event) {
                return !empty($event) && is_array($event) && !empty($event['type']) && !empty($event[$this->pmtaMessageIdHeaderKey]);
            })->all();

        // there's nothing we can do at this point...
        if (empty($events) || !is_array($events)) {
            app()->end();
            return;
        }

        foreach ($events as $event) {
            if (empty($event['type']) || empty($event[$this->pmtaMessageIdHeaderKey])) {
                continue;
            }

            $event[$this->pmtaMessageIdHeaderKey] = str_replace(['<', '>'], '', $event[$this->pmtaMessageIdHeaderKey]);
            $messageId = $event[$this->pmtaMessageIdHeaderKey];

            $criteria = new CDbCriteria();
            $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
            $criteria->params = [
                'email_message_id' => (string)$messageId,
                'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
            ];

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
            }

            if (empty($deliveryLog)) {
                continue;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_id'    => $deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            // bounces
            if (in_array($event['type'], ['b', 'rb', 'rs']) && !empty($event['bounceCat'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id    = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id  = (int)$subscriber->subscriber_id;
                $bounceLog->message        = $event['dsnDiag'] ?? 'BOUNCED BACK';
                $bounceLog->bounce_type    = CampaignBounceLog::BOUNCE_INTERNAL;

                if (in_array($event['bounceCat'], ['bad-mailbox', 'inactive-mailbox', 'bad-domain'])) {
                    $bounceLog->bounce_type = CampaignBounceLog::BOUNCE_HARD;
                } elseif (in_array($event['bounceCat'], ['quota-issues', 'no-answer-from-host', 'relaying-issues', 'routing-errors'])) {
                    $bounceLog->bounce_type = CampaignBounceLog::BOUNCE_SOFT;
                }

                $bounceLog->save();

                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }

            // FBL
            if (in_array($event['type'], ['f'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            }
        }
    }

    /**
     * Process Amazon SES
     *
     * @param DeliveryServer $server
     *
     * @return void
     * @throws CException
     */
    public function processAmazonSes($server)
    {
        $message   = Aws\Sns\Message::fromRawPostData();
        $validator = new Aws\Sns\MessageValidator([$this, '_amazonFetchRemote']);

        try {
            $validator->validate($message);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            app()->end();
            return;
        }

        if ($message['Type'] === 'SubscriptionConfirmation') {
            try {
                $types  = DeliveryServer::getTypesMapping();
                $type   = $types[$server->type];
                $server = DeliveryServer::model($type)->findByPk((int)$server->server_id);
                $result = $server->getSnsClient()->confirmSubscription([
                    'TopicArn'  => $message['TopicArn'],
                    'Token'     => $message['Token'],
                ]);
                if (stripos($result->get('SubscriptionArn'), 'pending') === false) {
                    $server->subscription_arn = $result->get('SubscriptionArn');
                    $server->save(false);
                }
                app()->end();
                return;
            } catch (Exception $e) {
            }

            $client = new GuzzleHttp\Client();
            $client->get((string)$message['SubscribeURL']);
            app()->end();
            return;
        }

        if ($message['Type'] !== 'Notification') {
            app()->end();
            return;
        }

        $data = new CMap((array)json_decode((string)$message['Message'], true));
        if (!$data->itemAt('notificationType') || $data->itemAt('notificationType') == 'AmazonSnsSubscriptionSucceeded' || !$data->itemAt('mail')) {
            app()->end();
            return;
        }

        $mailMessage = $data->itemAt('mail');
        if (empty($mailMessage['messageId'])) {
            app()->end();
            return;
        }
        $messageId = $mailMessage['messageId'];

        $criteria = new CDbCriteria();
        $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
        $criteria->params = [
            'email_message_id' => (string)$messageId,
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ];

        $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
        }

        if (empty($deliveryLog)) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => (int)$campaign->list_id,
            'subscriber_id'    => (int)$deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if ($data->itemAt('notificationType') == 'Bounce' && ($bounce = $data->itemAt('bounce'))) {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = $bounce['bouncedRecipients'][0]['diagnosticCode'] ?? 'BOUNCED BACK';
            $bounceLog->bounce_type     = $bounce['bounceType'] !== 'Permanent' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            if ($bounceLog->bounce_type === CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }
            app()->end();
            return;
        }

        if ($data->itemAt('notificationType') == 'Complaint' && ($complaint = $data->itemAt('complaint'))) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Helper for \Aws\Sns\MessageValidator because otherwise it uses file_get_contents to fetch remote data
     * and this might be disabled oin many hosts
     *
     * @param string $url
     * @return string
     */
    public function _amazonFetchRemote($url)
    {
        try {
            $content = (string)(new GuzzleHttp\Client())->get($url)->getBody();
        } catch (Exception $e) {
            $content = '';
        }
        return $content;
    }

    /**
     * Process Mailgun
     * @throws CException
     * @return void
     */
    public function processMailgun()
    {
        $event    = request()->getPost('event');
        $metaData = request()->getPost('metadata');

        if (empty($metaData) || empty($event)) {
            $this->processMailgunV2();
        } else {
            $this->processMailgunV1();
        }
    }

    /**
     * Process Sendgrid
     *
     * @return void
     * @throws CException
     */
    public function processSendgrid()
    {
        $events = (string)file_get_contents('php://input');
        if (empty($events)) {
            app()->end();
            return;
        }

        $events = json_decode($events, true);
        if (empty($events) || !is_array($events)) {
            $events = [];
        }

        foreach ($events as $evt) {
            if (empty($evt['event']) || !in_array($evt['event'], ['dropped', 'bounce', 'spamreport'])) {
                continue;
            }

            if (empty($evt['campaign_uid']) || empty($evt['subscriber_uid'])) {
                continue;
            }

            $campaignUid   = trim((string)$evt['campaign_uid']);
            $subscriberUid = trim((string)$evt['subscriber_uid']);

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByUid($campaignUid);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'           => $campaign->list_id,
                'subscriber_uid'    => $subscriberUid,
                'status'            => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            // https://sendgrid.com/docs/API_Reference/Webhooks/event.html
            if (in_array($evt['event'], ['dropped'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id   = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
                $bounceLog->message       = $evt['reason'] ?? $evt['event'];
                $bounceLog->message       = !empty($bounceLog->message) ? $bounceLog->message : 'Internal Bounce';
                $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_INTERNAL;
                $bounceLog->save();

                continue;
            }

            if (in_array($evt['event'], ['bounce'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id   = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
                $bounceLog->message       = $evt['reason'] ?? 'BOUNCED BACK';
                $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_HARD;
                $bounceLog->save();

                // @phpstan-ignore-next-line
                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }

            if (in_array($evt['event'], ['spamreport'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
                continue;
            }
        }

        app()->end();
    }

    /**
     * Process EE
     *
     * @return void
     * @throws CException
     */
    public function processElasticemail()
    {
        $category    = trim((string)request()->getQuery('category'));
        $messageId   = trim((string)request()->getQuery('messageid'));
        $status      = trim((string)request()->getQuery('status'));

        if (empty($messageId) || empty($category)) {
            app()->end();
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
        $criteria->params = [
            'email_message_id' => (string)$messageId,
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ];

        $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
        }

        if (empty($deliveryLog)) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => (int)$campaign->list_id,
            'subscriber_id'    => (int)$deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        // All categories:
        // https://elasticemail.com/support/delivery/http-web-notification
        if ($status == 'AbuseReport') {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if ($status == 'Unsubscribed') {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        if ($status == 'Error') {
            $categoryID           = strtolower((string)$category);
            $hardBounceCategories = ['NoMailbox', 'AccountProblem'];
            $hardBounceCategories = array_map('strtolower', $hardBounceCategories);

            $bounceType = null;

            if (in_array($categoryID, $hardBounceCategories)) {
                $bounceType = CampaignBounceLog::BOUNCE_HARD;
            } else {
                $bounceType = CampaignBounceLog::BOUNCE_SOFT;
            }

            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = $category;
            $bounceLog->bounce_type     = $bounceType;
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }

            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Process DynEmail
     *
     * @param DeliveryServer $server
     *
     * @return void
     * @throws CException
     */
    public function processDyn($server)
    {
        $event      = (string)request()->getQuery('event', '');
        $bounceRule = (string)request()->getQuery('rule', (string)request()->getQuery('bouncerule', '')); // bounce rule
        $bounceType = (string)request()->getQuery('type', (string)request()->getQuery('bouncetype', '')); // bounce type
        $campaign   = (string)request()->getQuery('campaign', ''); // campaign uid
        $subscriber = (string)request()->getQuery('subscriber', ''); // subscriber uid

        $allowedEvents = ['bounce', 'complaint', 'unsubscribe'];
        if (!in_array($event, $allowedEvents)) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByUid((string)$campaign);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $subscriber,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if ($event == 'bounce') {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = (string)$bounceRule;
            $bounceLog->bounce_type     = $bounceType == 'soft' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist((string)$bounceLog->message);
            }
            app()->end();
            return;
        }

        // remove from suppression list.
        if ($event == 'complaint') {
            $url = sprintf('https://api.email.dynect.net/rest/json/suppressions/activate?apikey=%s&emailaddress=%s', $server->password, urlencode($subscriber->email));
            (new GuzzleHttp\Client())->post($url, ['timeout' => 5]);
        }

        if (in_array($event, ['complaint'])) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if (in_array($event, ['unsubscribe'])) {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Process Sparkpost
     *
     * @return void
     * @throws CException
     */
    public function processSparkpost()
    {
        $events = (string)file_get_contents('php://input');
        if (empty($events)) {
            app()->end();
            return;
        }
        $events = json_decode($events, true);

        if (empty($events) || !is_array($events)) {
            $events = [];
        }

        foreach ($events as $evt) {
            if (!empty($evt['msys']['message_event'])) {
                $evt = $evt['msys']['message_event'];
            } elseif (!empty($evt['msys']['unsubscribe_event'])) {
                $evt = $evt['msys']['unsubscribe_event'];
            } else {
                continue;
            }

            if (empty($evt['type']) || !in_array($evt['type'], ['bounce', 'spam_complaint', 'list_unsubscribe', 'link_unsubscribe'])) {
                continue;
            }

            if (empty($evt['rcpt_meta']) || empty($evt['rcpt_meta']['campaign_uid']) || empty($evt['rcpt_meta']['subscriber_uid'])) {
                continue;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByUid($evt['rcpt_meta']['campaign_uid']);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_uid'   => $evt['rcpt_meta']['subscriber_uid'],
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            if (in_array($evt['type'], ['bounce', 'out_of_band'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                // https://support.sparkpost.com/customer/portal/articles/1929896-bounce-classification-codes
                $bounceType = CampaignBounceLog::BOUNCE_INTERNAL;
                if (in_array($evt['bounce_class'], [10, 30, 90])) {
                    $bounceType = CampaignBounceLog::BOUNCE_HARD;
                } elseif (in_array($evt['bounce_class'], [20, 40, 60])) {
                    $bounceType = CampaignBounceLog::BOUNCE_SOFT;
                }

                $defaultBounceMessage = 'BOUNCED BACK';
                if ($bounceType == CampaignBounceLog::BOUNCE_INTERNAL) {
                    $defaultBounceMessage = 'Internal Bounce';
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
                $bounceLog->message         = $evt['reason'] ?? $defaultBounceMessage;
                $bounceLog->bounce_type     = $bounceType;
                $bounceLog->save();

                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }

            if (in_array($evt['type'], ['spam_complaint'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
                continue;
            }

            if (in_array($evt['type'], ['list_unsubscribe', 'link_unsubscribe'])) {
                $subscriber->unsubscribeByCampaign($campaign);
                continue;
            }
        }

        app()->end();
    }

    /**
     * Process Pepipost
     *
     * @return void
     * @throws CException
     */
    public function processPepipost()
    {
        $events = (string)file_get_contents('php://input');
        if (empty($events)) {
            app()->end();
            return;
        }
        $events = json_decode($events, true);

        if (empty($events) || !is_array($events)) {
            $events = [];
        }

        foreach ($events as $evt) {
            if (empty($evt['TRANSID']) || empty($evt['X-APIHEADER']) || empty($evt['EVENT'])) {
                continue;
            }

            if (!in_array($evt['EVENT'], ['bounced', 'unsubscribed', 'spam'])) {
                continue;
            }

            $metaData = (array)json_decode(trim((string)str_replace('\"', '"', $evt['X-APIHEADER']), '"'), true);
            if (empty($metaData['campaign_uid']) || empty($metaData['subscriber_uid'])) {
                continue;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByUid((string)$metaData['campaign_uid']);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_uid'   => $metaData['subscriber_uid'],
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            if (in_array($evt['EVENT'], ['bounced'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceType = CampaignBounceLog::BOUNCE_INTERNAL;
                if ($evt['BOUNCE_TYPE'] == 'HARDBOUNCE') {
                    $bounceType = CampaignBounceLog::BOUNCE_HARD;
                } elseif ($evt['BOUNCE_TYPE'] == 'SOFTBOUNCE') {
                    $bounceType = CampaignBounceLog::BOUNCE_SOFT;
                }

                $defaultBounceMessage = 'BOUNCED BACK';
                if ($bounceType == CampaignBounceLog::BOUNCE_INTERNAL) {
                    $defaultBounceMessage = 'Internal Bounce';
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
                $bounceLog->message         = $evt['BOUNCE_REASON'] ?? $defaultBounceMessage;
                $bounceLog->bounce_type     = $bounceType;
                $bounceLog->save();

                if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }

            if (in_array($evt['EVENT'], ['spam'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
                continue;
            }

            if (in_array($evt['EVENT'], ['unsubscribe'])) {
                $subscriber->unsubscribeByCampaign($campaign);
                continue;
            }
        }

        app()->end();
    }

    /**
     * Process MailJet
     *
     * @return void
     * @throws CException
     */
    public function processMailjet()
    {
        $events = (string)file_get_contents('php://input');
        if (empty($events)) {
            app()->end();
            return;
        }

        $events = json_decode($events, true);

        if (empty($events) || !is_array($events)) {
            $events = [];
        }

        if (isset($events['event'])) {
            $events = [$events];
        }

        foreach ($events as $event) {
            if (!isset($event['MessageID'], $event['event'])) {
                continue;
            }

            $messageId = $event['MessageID'];

            $criteria = new CDbCriteria();
            $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
            $criteria->params = [
                'email_message_id' => (string)$messageId,
                'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
            ];

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
            }

            if (empty($deliveryLog)) {
                continue;
            }

            /** @var Campaign|null $campaign */
            $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
            if (empty($campaign)) {
                continue;
            }

            /** @var ListSubscriber|null $subscriber */
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id'          => $campaign->list_id,
                'subscriber_id'    => $deliveryLog->subscriber_id,
                'status'           => ListSubscriber::STATUS_CONFIRMED,
            ]);

            if (empty($subscriber)) {
                continue;
            }

            if (in_array($event['event'], ['bounce', 'blocked'])) {
                $count = CampaignBounceLog::model()->countByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                if (!empty($count)) {
                    continue;
                }

                $bounceLog = new CampaignBounceLog();
                $bounceLog->campaign_id     = (int)$campaign->campaign_id;
                $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
                $bounceLog->message         = $event['error'] ?? 'BOUNCED BACK';
                $bounceLog->bounce_type     = empty($event['hard_bounce']) ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;
                $bounceLog->save();

                if (!empty($event['hard_bounce'])) {
                    $subscriber->addToBlacklist($bounceLog->message);
                }

                continue;
            }

            if (in_array($event['event'], ['spam'])) {
                /** @var OptionCronProcessFeedbackLoopServers $fbl */
                $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
                continue;
            }

            if (in_array($event['event'], ['unsub'])) {
                $subscriber->unsubscribeByCampaign($campaign);
                continue;
            }
        }

        app()->end();
    }

    /**
     * Process SendinBlue
     *
     * @return void
     * @throws CException
     */
    public function processSendinblue()
    {
        $event = (string)file_get_contents('php://input');
        if (empty($event)) {
            app()->end();
            return;
        }

        $event = json_decode($event, true);

        if (empty($event) || !is_array($event) || empty($event['event']) || empty($event['message-id'])) {
            app()->end();
            return;
        }

        $messageId = $event['message-id'];

        $criteria = new CDbCriteria();
        $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
        $criteria->params = [
            'email_message_id' => (string)$messageId,
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ];

        $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
        }

        if (empty($deliveryLog)) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_id'    => $deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if (in_array($event['event'], ['hard_bounce', 'soft_bounce', 'blocked', 'invalid_email'])) {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = $event['reason'] ?? 'BOUNCED BACK';
            $bounceLog->bounce_type     = $event['event'] == 'soft_bounce' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }

            app()->end();
            return;
        }

        if (in_array($event['event'], ['spam'])) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if (in_array($event['event'], ['unsubscribed'])) {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Process Tipimail
     *
     * @return void
     * @throws CException
     */
    public function processTipimail()
    {
        $event = (string)file_get_contents('php://input');
        if (empty($event)) {
            app()->end();
            return;
        }

        $event = json_decode($event, true);

        if (empty($event) || !is_array($event) || empty($event['status'])) {
            app()->end();
            return;
        }

        if (empty($event['meta']) || empty($event['meta']['campaign_uid']) || empty($event['meta']['subscriber_uid'])) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByAttributes([
            'campaign_uid' => $event['meta']['campaign_uid'],
        ]);

        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $event['meta']['subscriber_uid'],
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if (in_array($event['status'], ['error', 'rejected', 'hardbounced'])) {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id    = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id  = (int)$subscriber->subscriber_id;
            $bounceLog->message        = $event['description'] ?? 'BOUNCED BACK';
            $bounceLog->bounce_type    = CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            $subscriber->addToBlacklist($bounceLog->message);

            app()->end();
            return;
        }

        if (in_array($event['status'], ['complaint'])) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if (in_array($event['status'], ['unsubscribed'])) {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Process Postmark
     *
     * @return void
     * @throws CException
     */
    public function processPostmark()
    {
        $event = (string)file_get_contents('php://input');

        if (empty($event)) {
            app()->end();
            return;
        }

        $event = (array)json_decode($event, true);
        if (empty($event) || !is_array($event) || empty($event['MessageID'])) {
            app()->end();
            return;
        }

        $messageId = $event['MessageID'];

        $criteria = new CDbCriteria();
        $criteria->addCondition('`email_message_id` = :email_message_id AND `status` = :status');
        $criteria->params = [
            'email_message_id' => (string)$messageId,
            'status'           => CampaignDeliveryLog::STATUS_SUCCESS,
        ];

        $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
        if (empty($deliveryLog)) {
            $deliveryLog = CampaignDeliveryLogArchive::model()->find($criteria);
        }

        if (empty($deliveryLog)) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByPk((int)$deliveryLog->campaign_id);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => (int)$campaign->list_id,
            'subscriber_id'    => (int)$deliveryLog->subscriber_id,
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        // Please see the bounce types here: https://postmarkapp.com/developer/api/bounce-api#bounce-types
        $bounceTypes = [
            'HardBounce', 'SoftBounce', 'Blocked', 'BadEmailAddress', 'AutoResponder', 'DnsError', 'AddressChange',
            'ManuallyDeactivated', 'SMTPApiError', 'InboundError', 'DMARCPolicy',
        ];

        if (in_array($event['Type'], $bounceTypes)) {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $message = 'BOUNCED BACK';
            if (!empty($event['Details'])) {
                $message = $event['Details'];
            } elseif (!empty($event['Description'])) {
                $message = $event['Description'];
            }

            $mapping = [
                CampaignBounceLog::BOUNCE_INTERNAL => ['Blocked', 'SMTPApiError', 'InboundError', 'DMARCPolicy'],
                CampaignBounceLog::BOUNCE_HARD     => ['HardBounce', 'BadEmailAddress'],
                CampaignBounceLog::BOUNCE_SOFT     => ['SoftBounce', 'AutoResponder', 'AddressChange', 'ManuallyDeactivated'],
            ];

            $bounceType = collect($mapping)->reject(function (array $value, $key) use (&$event) {
                return !in_array($event['Type'], $value);
            })->keys()->first();

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id     = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
            $bounceLog->message         = (string)$message;
            $bounceLog->bounce_type     = (string)($bounceType ?? CampaignBounceLog::BOUNCE_INTERNAL);
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist((string)$bounceLog->message);
            }

            app()->end();
            return;
        }

        if (in_array($event['Type'], ['SpamNotification'])) {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if (in_array($event['Type'], ['Unsubscribe'])) {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function end($message = 'OK')
    {
        if ($message) {
            echo $message;
        }
        app()->end();
    }

    /**
     * Process Mailgun V2
     * @throws CException
     * @return void
     */
    protected function processMailgunV2()
    {
        /** @var array $payload */
        $payload = (array)json_decode((string)file_get_contents('php://input'), true);

        if (
            empty($payload) ||
            empty($payload['event-data']) ||
            empty($payload['event-data']['event']) ||
            empty($payload['event-data']['user-variables']['metadata'])
        ) {
            app()->end();
            return;
        }

        /** @var array $eventData */
        $eventData = $payload['event-data'];
        $event     = strtolower($eventData['event']);
        $metaData  = (array)json_decode($eventData['user-variables']['metadata'], true);

        if (empty($metaData) || empty($event)) {
            app()->end();
            return;
        }

        if (empty($metaData['campaign_uid']) || empty($metaData['subscriber_uid'])) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByAttributes([
            'campaign_uid' => $metaData['campaign_uid'],
        ]);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $metaData['subscriber_uid'],
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if ($event == 'failed') {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => $campaign->campaign_id,
                'subscriber_id' => $subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $reason      = !empty($eventData['reason']) ? $eventData['reason'] : '';
            $description = !empty($eventData['delivery-status']['description']) ? $eventData['delivery-status']['description'] : '-';
            $message     = !empty($eventData['delivery-status']['message']) ? $eventData['delivery-status']['message'] : '-';

            $bounceMessage = json_encode([
                'message'     => (string)$message,
                'description' => (string)$description,
                'reason'      => (string)$reason,
            ]);

            $bounceType = CampaignBounceLog::BOUNCE_INTERNAL;
            if (!empty($eventData['severity']) && $eventData['severity'] === 'permanent') {
                $bounceType = CampaignBounceLog::BOUNCE_HARD;
            }

            if (!empty($eventData['severity']) && $eventData['severity'] === 'temporary') {
                $bounceType = CampaignBounceLog::BOUNCE_SOFT;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
            $bounceLog->message       = (string)$bounceMessage;
            $bounceLog->bounce_type   = $bounceType;
            $bounceLog->save();

            if ($bounceType === CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist($bounceLog->message);
            }

            app()->end();
            return;
        }

        if ($event === 'complained') {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        if ($event === 'unsubscribed') {
            $subscriber->unsubscribeByCampaign($campaign);
            app()->end();
            return;
        }

        app()->end();
    }

    /**
     * Process Mailgun V1
     *
     * @return void
     * @throws CException
     */
    protected function processMailgunV1()
    {
        $event    = (string)request()->getPost('event');
        $metaData = (string)request()->getPost('metadata');

        if (empty($metaData) || empty($event)) {
            app()->end();
            return;
        }

        $metaData = (array)json_decode($metaData, true);
        if (empty($metaData['campaign_uid']) || empty($metaData['subscriber_uid'])) {
            app()->end();
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByAttributes([
            'campaign_uid' => $metaData['campaign_uid'],
        ]);
        if (empty($campaign)) {
            app()->end();
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'          => $campaign->list_id,
            'subscriber_uid'   => $metaData['subscriber_uid'],
            'status'           => ListSubscriber::STATUS_CONFIRMED,
        ]);

        if (empty($subscriber)) {
            app()->end();
            return;
        }

        if ($event == 'bounced') {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
            $bounceLog->message       = (string)request()->getPost('notification', (string)request()->getPost('error', (string)request()->getPost('code', '')));
            $bounceLog->bounce_type   = CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            $subscriber->addToBlacklist((string)$bounceLog->message);

            app()->end();
            return;
        }

        if ($event == 'dropped') {
            $count = CampaignBounceLog::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);

            if (!empty($count)) {
                app()->end();
                return;
            }

            $bounceLog = new CampaignBounceLog();
            $bounceLog->campaign_id   = (int)$campaign->campaign_id;
            $bounceLog->subscriber_id = (int)$subscriber->subscriber_id;
            $bounceLog->message       = (string)request()->getPost('description', (string)request()->getPost('error', (string)request()->getPost('reason', '')));
            $bounceLog->bounce_type   = (string)request()->getPost('reason') != 'hardfail' ? CampaignBounceLog::BOUNCE_SOFT : CampaignBounceLog::BOUNCE_HARD;
            $bounceLog->save();

            if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                $subscriber->addToBlacklist((string)$bounceLog->message);
            }

            app()->end();
            return;
        }

        if ($event == 'complained') {
            /** @var OptionCronProcessFeedbackLoopServers $fbl */
            $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
            $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);
            app()->end();
            return;
        }

        app()->end();
    }
}
