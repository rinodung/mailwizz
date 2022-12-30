<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign".
 *
 * The followings are the available columns in table 'campaign':
 * @property int|null $campaign_id
 * @property string $campaign_uid
 * @property int|string $customer_id
 * @property int|string $list_id
 * @property int|string|null $segment_id
 * @property int|null $group_id
 * @property int|null $send_group_id
 * @property string $type
 * @property string $name
 * @property string $from_name
 * @property string $from_email
 * @property string $to_name
 * @property string $reply_to
 * @property string $subject
 * @property string $subject_encoded
 * @property mixed $send_at
 * @property mixed $started_at
 * @property mixed $finished_at
 * @property string $delivery_logs_archived
 * @property int $priority
 * @property mixed $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignGroup $group
 * @property CampaignSendGroup $sendGroup
 * @property Customer $customer
 * @property Lists $list
 * @property ListSegment $segment
 * @property CampaignBounceLog[] $bounceLogs
 * @property CampaignComplainLog[] $complaintLogs
 * @property CampaignDeliveryLog[] $deliveryLogs
 * @property CampaignDeliveryLogArchive[] $deliveryLogsArchive
 * @property CampaignExtraTag[] $extraTags
 * @property CampaignForwardFriend[] $forwardFriends
 * @property CampaignOpenActionListField[] $openActionListFields
 * @property CampaignSentActionListField[] $sentActionListFields
 * @property CampaignOpenActionSubscriber[] $openActionSubscribers
 * @property CampaignSentActionSubscriber[] $sentActionSubscribers
 * @property CampaignTemplateUrlActionListField[] $urlActionListFields
 * @property CampaignTemplateUrlActionSubscriber[] $urlActionSubscribers
 * @property CampaignOption $option
 * @property CampaignTemplate $template
 * @property CampaignTemplate[] $templates
 * @property CampaignAttachment[] $attachments
 * @property DeliveryServer[] $deliveryServers
 * @property CampaignTrackOpen[] $trackOpens
 * @property CampaignTrackUnsubscribe[] $trackUnsubscribes
 * @property CampaignUrl[] $urls
 * @property CampaignRandomContent[] $randomContents
 * @property CampaignOptionShareReports $shareReports
 *
 * The followings are the available model behaviors:
 * @property CampaignStatsProcessorBehavior $statsBehavior
 * @property CampaignQueueTableBehavior $queueTable
 *
 * The followings are the available model getters:
 * @property string $dateAdded
 * @property string $sendAt
 * @property bool $editable
 * @property bool $isDraft
 */
class Campaign extends ActiveRecord
{
    /**
     * Statuses list
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_SENDING = 'pending-sending';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PAUSED = 'paused';
    const STATUS_PENDING_DELETE = 'pending-delete';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PENDING_APPROVE = 'pending-approve';

    /**
     * Types list
     */
    const TYPE_REGULAR = 'regular';
    const TYPE_AUTORESPONDER = 'autoresponder';

    /**
     * Bulk actions list
     */
    const BULK_ACTION_PAUSE_UNPAUSE = 'pause-unpause';
    const BULK_ACTION_MARK_SENT = 'mark-sent';
    const BULK_EXPORT_BASIC_STATS = 'export-basic-stats';
    const BULK_ACTION_SEND_TEST_EMAIL = 'send-test-email';
    const BULK_ACTION_SHARE_CAMPAIGN_CODE = 'share-campaign-code';
    const BULK_ACTION_COMPARE_CAMPAIGNS = 'compare-campaigns';

    /**
     * @var string
     */
    public $search_recurring;

    /**
     * @var string
     */
    public $search_ar_event;

    /**
     * @var string
     */
    public $search_ar_time;

    /**
     * @var string
     */
    public $search_ar_time_res;

    /**
     * @var string
     */
    public $search_template_name;

    /**
     * @var string
     */
    protected $_currentSubject = '';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [];

        if (app_param('send.campaigns.command.useTempQueueTables', false)) {
            $behaviors['queueTable'] = [
                'class' => 'common.components.db.behaviors.CampaignQueueTableBehavior',
            ];
        }

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, list_id', 'required', 'on' => 'step-name, step-confirm'],
            ['from_name, from_email, subject, reply_to, to_name', 'required', 'on' => 'step-setup, step-confirm'],
            ['send_at', 'required', 'on' => 'step-confirm'],

            ['list_id, segment_id, group_id', 'numerical', 'integerOnly' => true],
            ['list_id', 'exist', 'className' => Lists::class],
            ['segment_id', 'exist', 'className' => ListSegment::class],

            ['group_id', 'exist', 'className' => CampaignGroup::class],
            ['send_group_id', 'exist', 'className' => CampaignSendGroup::class, 'attributeName' => 'group_id'],
            ['name, to_name, from_name', 'length', 'max'=>255],
            ['subject', 'filter', 'filter' => 'trim'],
            ['subject', 'length', 'max' => 500],
            ['from_email, reply_to', 'length', 'max'=>100],
            ['from_email, reply_to', '_validateEMailWithTag'],
            ['type', 'in', 'range' => array_keys($this->getTypesList())],
            ['send_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss', 'on' => 'step-confirm'],

            // The following rule is used by search().
            ['campaign_uid, customer_id, group_id, send_group_id, list_id, name, type, status, search_recurring, search_ar_event, search_ar_time, search_template_name', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'group'                 => [self::BELONGS_TO, CampaignGroup::class, 'group_id'],
            'sendGroup'             => [self::BELONGS_TO, CampaignSendGroup::class, 'send_group_id'],
            'list'                  => [self::BELONGS_TO, Lists::class, 'list_id'],
            'segment'               => [self::BELONGS_TO, ListSegment::class, 'segment_id'],
            'bounceLogs'            => [self::HAS_MANY, CampaignBounceLog::class, 'campaign_id'],
            'complaintLogs'         => [self::HAS_MANY, CampaignComplainLog::class, 'campaign_id'],
            'deliveryLogs'          => [self::HAS_MANY, CampaignDeliveryLog::class, 'campaign_id'],
            'deliveryLogsArchive'   => [self::HAS_MANY, CampaignDeliveryLogArchive::class, 'campaign_id'],
            'extraTags'             => [self::HAS_MANY, CampaignExtraTag::class, 'campaign_id'],
            'forwardFriends'        => [self::HAS_MANY, CampaignForwardFriend::class, 'campaign_id'],
            'openActionListFields'  => [self::HAS_MANY, CampaignOpenActionListField::class, 'campaign_id'],
            'sentActionListFields'  => [self::HAS_MANY, CampaignSentActionListField::class, 'campaign_id'],
            'openActionSubscribers' => [self::HAS_MANY, CampaignOpenActionSubscriber::class, 'campaign_id'],
            'sentActionSubscribers' => [self::HAS_MANY, CampaignSentActionSubscriber::class, 'campaign_id'],
            'urlActionListFields'   => [self::HAS_MANY, CampaignTemplateUrlActionListField::class, 'campaign_id'],
            'urlActionSubscribers'  => [self::HAS_MANY, CampaignTemplateUrlActionSubscriber::class, 'campaign_id'],
            'customer'              => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'option'                => [self::HAS_ONE, CampaignOption::class, 'campaign_id'],
            'shareReports'          => [self::HAS_ONE, CampaignOptionShareReports::class, 'campaign_id'],
            'template'              => [self::HAS_ONE, CampaignTemplate::class, 'campaign_id'],
            'templates'             => [self::HAS_MANY, CampaignTemplate::class, 'campaign_id'],
            'attachments'           => [self::HAS_MANY, CampaignAttachment::class, 'campaign_id'],
            'deliveryServers'       => [self::MANY_MANY, DeliveryServer::class, '{{campaign_to_delivery_server}}(campaign_id, server_id)'],
            'trackOpens'            => [self::HAS_MANY, CampaignTrackOpen::class, 'campaign_id'],
            'trackUnsubscribes'     => [self::HAS_MANY, CampaignTrackUnsubscribe::class, 'campaign_id'],
            'urls'                  => [self::HAS_MANY, CampaignUrl::class, 'campaign_id'],
            'randomContents'        => [self::HAS_MANY, CampaignRandomContent::class, 'campaign_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'campaign_id'           => $this->t('ID'),
            'campaign_uid'          => $this->t('Unique ID'),
            'customer_id'           => $this->t('Customer'),
            'list_id'               => $this->t('List'),
            'segment_id'            => $this->t('Segment'),
            'group_id'              => $this->t('Group'),
            'send_group_id'         => $this->t('Send group'),
            'name'                  => $this->t('Campaign name'),
            'type'                  => $this->t('Type'),
            'from_name'             => $this->t('From name'),
            'from_email'            => $this->t('From email'),
            'to_name'               => $this->t('To name'),
            'reply_to'              => $this->t('Reply to'),
            'confirmed_reply_to'    => $this->t('Confirmed reply to'),
            'confirmation_code'     => $this->t('Confirmation code'),
            'subject'               => $this->t('Subject'),
            'send_at'               => $this->t('Send at'),
            'started_at'            => $this->t('Started at'),
            'finished_at'           => $this->t('Finished at'),

            'lastOpen'              => $this->t('Last open'),
            'totalDeliveryTime'     => $this->t('Total delivery time'),
            'webVersion'            => $this->t('Web version'),
            'search_recurring'      => $this->t('Recurring'),
            'search_ar_event'       => $this->t('Event'),
            'search_ar_time'        => $this->t('Time'),
            'search_template_name'  => $this->t('Template'),

            //
            'bounceRate'            => $this->t('Bounce rate'),
            'hardBounceRate'        => $this->t('Hard bounce rate'),
            'softBounceRate'        => $this->t('Soft bounce rate'),
            'internalBounceRate'    => $this->t('Internal bounce rate'),
            'unsubscribesRate'      => $this->t('Unsubscribes rate'),
            'complaintsRate'        => $this->t('Complaints rate'),

            'gridViewRecipients'    => $this->t('Recipients'),
            'gridViewSent'          => $this->t('Sent'),
            'gridViewDelivered'     => $this->t('Delivered'),
            'gridViewOpens'         => $this->t('Opens'),
            'gridViewClicks'        => $this->t('Clicks'),
            'gridViewBounces'       => $this->t('Bounces'),
            'gridViewUnsubs'        => $this->t('Unsubs'),
        ];

        if ($this->getIsAutoresponder()) {
            $labels['send_at'] = $this->t('Activate at');
        }

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $expression = '
            @t:=t.type,
            @n:=CAST(option.autoresponder_time_value AS UNSIGNED),
            @p:=option.autoresponder_time_unit,
            CASE
              WHEN @t=\'autoresponder\' AND @p=\'year\' THEN DATE_ADD(NOW(), INTERVAL @n YEAR)
              WHEN @t=\'autoresponder\' AND @p=\'month\' THEN DATE_ADD(NOW(), INTERVAL @n MONTH)
              WHEN @t=\'autoresponder\' AND @p=\'day\' THEN DATE_ADD(NOW(), INTERVAL @n DAY)
              WHEN @t=\'autoresponder\' AND @p=\'hour\' THEN DATE_ADD(NOW(), INTERVAL @n HOUR)
              WHEN @t=\'autoresponder\' AND @p=\'minute\' THEN DATE_ADD(NOW(), INTERVAL @n MINUTE)
              WHEN @t=\'autoresponder\' AND @p=\'second\' THEN DATE_ADD(NOW(), INTERVAL @n SECOND)
              ELSE NOW()
            END as search_ar_time_res
        ';

        $criteria->with['option'] = [
            'select'   => ['*', new CDbExpression($expression)],
            'together' => true,
            'joinType' => 'INNER JOIN',
        ];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with['customer'] = [
                    'condition' => '
                        customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name
                    ',
                    'params' => [':name' => '%' . $customerId . '%'],
                ];
            }
        }

        // since 1.3.5
        if (!empty($this->list_id)) {
            $listId = (string)$this->list_id;
            if (is_numeric($listId)) {
                $criteria->compare('t.list_id', $listId);
            } else {
                $criteria->with['list'] = [
                    'condition' => 'list.name LIKE :listName',
                    'params'    => [':listName' => '%' . $listId . '%'],
                ];
            }
        }

        // since 1.9.32
        if (!empty($this->segment_id)) {
            $segmentId = (string)$this->segment_id;
            if (is_numeric($segmentId)) {
                $criteria->compare('t.segment_id', $segmentId);
            } else {
                $criteria->with['segment'] = [
                    'condition' => 'segment.name LIKE :segmentName',
                    'params'    => [':segmentName' => '%' . $segmentId . '%'],
                ];
            }
        }

        $criteria->compare('t.group_id', $this->group_id);
        $criteria->compare('t.send_group_id', $this->send_group_id);
        $criteria->compare('t.campaign_uid', $this->campaign_uid);
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.type', $this->type);

        if (empty($this->status)) {
            $criteria->compare('t.status', '<>' . self::STATUS_PENDING_DELETE);
        } elseif (is_array($this->status)) {
            $criteria->addInCondition('t.status', $this->status);
        } elseif (is_string($this->status)) {
            $criteria->compare('t.status', $this->status);
        }

        // 1.3.7.1
        if (!empty($this->search_recurring) || !empty($this->search_ar_event) || !empty($this->search_ar_time)) {
            if (!empty($this->search_recurring)) {
                $criteria->compare('option.cronjob_enabled', (string)$this->search_recurring === self::TEXT_NO ? 0 : 1);
            }

            if (!empty($this->search_ar_event)) {
                $criteria->compare('option.autoresponder_event', $this->search_ar_event);
            }

            if (!empty($this->search_ar_time)) {
                $criteria->addCondition('
                    CONCAT(option.autoresponder_time_value, " ", option.autoresponder_time_unit) LIKE :ar_time
                ');
                $criteria->params[':ar_time'] = '%' . $this->search_ar_time . '%';
            }
        }

        // 1.6.0
        if (!empty($this->search_template_name)) {
            $criteria->with['template'] = [
                'select'    => false,
                'joinType'  => 'INNER JOIN',
                'condition' => 'template.name LIKE :templateName',
                'params'    => [':templateName' => '%' . $this->search_template_name . '%'],
            ];
        }

        return new CActiveDataProvider(get_class($this), (array)hooks()->applyFilters('campaign_model_search_data_provider_config', [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'attributes' => [
                    'campaign_id',
                    'customer_id',
                    'campaign_uid',
                    'name',
                    'type',
                    'group_id',
                    'send_group_id',
                    'list_id',
                    'segment_id',
                    'send_at',
                    'started_at',
                    'status',

                    'search_ar_event' => [
                        'asc'  => 'option.autoresponder_event ASC',
                        'desc' => 'option.autoresponder_event DESC',
                    ],

                    'search_ar_time' => [
                        'asc'  => 'search_ar_time_res ASC',
                        'desc' => 'search_ar_time_res DESC',
                    ],
                ],
                'defaultOrder'  => [
                    'campaign_id'   => CSort::SORT_DESC,
                ],
            ],
        ]));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     *
     * @param string $className active record class name.
     *
     * @return Campaign
     */
    public static function model($className=__CLASS__)
    {
        /** @var self $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'campaigns';
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     */
    public function findByUid(string $campaign_uid): ?self
    {
        /** @var self $model */
        $model = self::model()->findByAttributes([
            'campaign_uid' => $campaign_uid,
        ]);

        return $model;
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'type'          => $this->t('The type of this campaign, either a regular one or autoresponder'),
            'name'          => $this->t('The campaign name, this is used internally so that you can differentiate between the campaigns. Will not be shown to subscribers.'),
            'list_id'       => $this->t('The list from where we will pick the subscribers. We will send to all the confirmed subscribers if no segment is specified.'),
            'segment_id'    => $this->t('Narrow the subscribers to a specific defined segment. If you have no segment so far, feel free to go ahead and create one to be used here.'),
            'send_group_id' => $this->t('Campaigns in same send group will allow you to send multiple campaigns to multiple list/segments without sending the email to same subscriber twice.'),
            'send_at'       => $this->t('Uses your account timezone in "{format}" format. Please make sure you use the date/time picker to set the value, do not enter it manually.', ['{format}' => $this->getDateTimeFormat()]),

            'from_name' => $this->t('This is the name of the "From" header used in campaigns, use a name that your subscribers will easily recognize, like your website name or company name.'),
            'from_email'=> $this->t('This is the email of the "From" header used in campaigns, use a name that your subscribers will easily recognize, containing your website name or company name.'),
            'subject'   => $this->t('Campaign subject. There are a few available tags for customization.'),
            'reply_to'  => $this->t('If a subscriber replies to your campaign, this is the email address where the reply will go.'),
            'to_name'   => $this->t('This is the "To" header shown in the campaign. There are a few available tags for customization.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'name'          => $this->t('I.E: Weekly digest subscribers'),
            'list_id'       => null,
            'segment_id'    => null,
            'send_group_id' => $this->t('Start typing...'),
            'send_at'       => $this->getDateTimeFormat(),

            'from_name' => $this->t('My Super Company INC'),
            'from_email'=> $this->t('newsletter@my-super-company.com'),
            'subject'   => $this->t('Weekly newsletter'),
            'reply_to'  => $this->t('reply@my-super-company.com'),
            'to_name'   => $this->t('[FNAME] [LNAME]'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return bool
     */
    public function pause(): bool
    {
        if (!$this->getCanBePaused()) {
            return false;
        }
        return $this->saveStatus(self::STATUS_PAUSED);
    }

    /**
     * @return bool
     * @throws CException
     */
    public function unpause(): bool
    {
        if (!$this->getIsPaused()) {
            return false;
        }
        return $this->saveStatus(self::STATUS_SENDING);
    }

    /**
     * @return bool
     * @throws CException
     */
    public function pauseUnpause(): bool
    {
        if ($this->getIsPaused()) {
            return $this->unpause();
        }
        return $this->pause();
    }

    /**
     * @param bool $notify
     *
     * @return bool
     */
    public function markPendingApprove(bool $notify = true): bool
    {
        $saved = $this->saveStatus(self::STATUS_PENDING_APPROVE);
        if ($saved && $notify) {
            $this->sendNotificationsForPendingApproveCampaign();
        }
        return $saved;
    }

    /**
     * @param string $reason
     *
     * @return bool
     */
    public function block(string $reason = ''): bool
    {
        if (!$this->getCanBeBlocked()) {
            return false;
        }
        $saved = $this->saveStatus(self::STATUS_BLOCKED);
        if ($saved && !empty($this->option)) {
            $this->option->setBlockedReason($reason);
        }
        if ($saved) {
            $this->sendNotificationsForBlockedCampaign();
        }
        return $saved;
    }

    /**
     * @return bool
     */
    public function unblock(): bool
    {
        if (!$this->getIsBlocked()) {
            return false;
        }

        if (!empty($this->option)) {
            $this->option->setBlockedReason();
        }

        return $this->saveStatus(self::STATUS_SENDING);
    }

    /**
     * @return bool
     */
    public function blockUnblock(): bool
    {
        if ($this->getIsBlocked()) {
            return $this->unblock();
        }
        return $this->block();
    }

    /**
     * @param bool $doTransaction
     *
     * @return Campaign|null
     * @throws CException
     */
    public function copy(bool $doTransaction = true): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {
            // 1.3.6.2
            hooks()->doAction('copy_campaign', new CAttributeCollection([
                'campaign' => $this,
                'copied'   => $copied,
            ]));
            return null;
        }

        $transaction = null;
        if ($doTransaction) {
            $transaction = db()->beginTransaction();
        }

        try {
            /** @var Campaign $campaign */
            $campaign = clone $this;
            $campaign->setIsNewRecord(true);
            $campaign->campaign_id  = null;
            $campaign->campaign_uid = $campaign->generateUid();
            $campaign->send_at      = null;
            $campaign->date_added   = MW_DATETIME_NOW;
            $campaign->last_updated = MW_DATETIME_NOW;
            $campaign->started_at   = null;
            $campaign->finished_at  = null;
            $campaign->status       = self::STATUS_DRAFT;
            $campaign->delivery_logs_archived = self::TEXT_NO;

            if (preg_match('/#(\d+)$/', $campaign->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $campaign->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $campaign->name);
            } else {
                $campaign->name .= ' #1';
            }

            if (!$campaign->save(false)) {
                throw new CException($campaign->shortErrors->getAllAsString());
            }

            // campaign options
            $option = !empty($this->option) ? clone $this->option : new CampaignOption();
            $option->setIsNewRecord(true);
            $option->campaign_id                = (int)$campaign->campaign_id;
            $option->giveup_counter             = 0;
            $option->cronjob_runs_counter       = 0;
            $option->cronjob_rescheduled        = self::TEXT_NO;
            $option->processed_count            = -1;
            $option->delivery_success_count     = -1;
            $option->delivery_error_count       = -1;
            $option->industry_processed_count   = -1;
            $option->bounces_count              = -1;
            $option->hard_bounces_count         = -1;
            $option->soft_bounces_count         = -1;
            $option->internal_bounces_count     = -1;
            $option->opens_count                = -1;
            $option->unique_opens_count         = -1;
            $option->clicks_count               = -1;
            $option->unique_clicks_count        = -1;

            $option->email_stats_sent           = 0;

            if (!$option->save()) {
                throw new Exception($option->shortErrors->getAllAsString());
            }

            // actions on open
            $openActions = CampaignOpenActionSubscriber::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($openActions as $action) {
                $action = clone $action;
                $action->setIsNewRecord(true);
                $action->action_id    = null;
                $action->campaign_id  = (int)$campaign->campaign_id;
                $action->date_added   = MW_DATETIME_NOW;
                $action->last_updated = MW_DATETIME_NOW;
                $action->save(false);
            }

            // actions on sent
            $sentActions = CampaignSentActionSubscriber::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($sentActions as $action) {
                $action = clone $action;
                $action->setIsNewRecord(true);
                $action->action_id    = null;
                $action->campaign_id  = (int)$campaign->campaign_id;
                $action->date_added   = MW_DATETIME_NOW;
                $action->last_updated = MW_DATETIME_NOW;
                $action->save(false);
            }

            // webhooks on open
            $openWebhooks = CampaignTrackOpenWebhook::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($openWebhooks as $openWebhook) {
                $openWebhook = clone $openWebhook;
                $openWebhook->setIsNewRecord(true);
                $openWebhook->webhook_id   = null;
                $openWebhook->campaign_id  = (int)$campaign->campaign_id;
                $openWebhook->save(false);
            }

            // webhooks on url
            $urlWebhooks = CampaignTrackUrlWebhook::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($urlWebhooks as $urlWebhook) {
                $urlWebhook = clone $urlWebhook;
                $urlWebhook->setIsNewRecord(true);
                $urlWebhook->webhook_id   = null;
                $urlWebhook->campaign_id  = (int)$campaign->campaign_id;
                $urlWebhook->save(false);
            }

            // extra tags
            $extraTags = CampaignExtraTag::model()->findAllByAttributes([
                'campaign_id' => $this->campaign_id,
            ]);
            foreach ($extraTags as $extraTag) {
                $extraTag = clone $extraTag;
                $extraTag->setIsNewRecord(true);
                $extraTag->tag_id       = null;
                $extraTag->campaign_id  = (int)$campaign->campaign_id;
                $extraTag->date_added   = MW_DATETIME_NOW;
                $extraTag->last_updated = MW_DATETIME_NOW;
                $extraTag->save(false);
            }

            // actions on open against custom fields
            $openListFieldActions = CampaignOpenActionListField::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($openListFieldActions as $action) {
                $action = clone $action;
                $action->setIsNewRecord(true);
                $action->action_id    = null;
                $action->campaign_id  = (int)$campaign->campaign_id;
                $action->date_added   = MW_DATETIME_NOW;
                $action->last_updated = MW_DATETIME_NOW;
                $action->save(false);
            }

            // actions on sent against custom fields
            $sentListFieldActions = CampaignSentActionListField::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($sentListFieldActions as $action) {
                $action = clone $action;
                $action->setIsNewRecord(true);
                $action->action_id    = null;
                $action->campaign_id  = (int)$campaign->campaign_id;
                $action->date_added   = MW_DATETIME_NOW;
                $action->last_updated = MW_DATETIME_NOW;
                $action->save(false);
            }

            // template related
            $templateClickActions = [];
            $templateClickActionsListFields = [];
            if (!empty($this->template)) {
                $templateClickActions = CampaignTemplateUrlActionSubscriber::model()->findAllByAttributes([
                    'campaign_id' => $this->campaign_id,
                    'template_id' => $this->template->template_id,
                ]);
                $templateClickActionsListFields = CampaignTemplateUrlActionListField::model()->findAllByAttributes([
                    'campaign_id' => $this->campaign_id,
                    'template_id' => $this->template->template_id,
                ]);
                $template = clone $this->template;
            } else {
                $template = new CampaignTemplate();
            }

            // random contents
            $randomContents = CampaignRandomContent::model()->findAllByAttributes([
                'campaign_id'   => $this->campaign_id,
            ]);
            foreach ($randomContents as $randomContent) {
                $randomContent               = clone $randomContent;
                $randomContent->setIsNewRecord(true);
                $randomContent->id           = null;
                $randomContent->campaign_id  = (int)$campaign->campaign_id;
                $randomContent->save(false);
            }

            // campaign template
            $template->setIsNewRecord(true);
            $template->template_id = null;
            $template->campaign_id = (int)$campaign->campaign_id;

            $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
            $oldCampaignFilesPath = $storagePath . '/cmp' . $this->campaign_uid;
            $newCampaignFilesPath = $storagePath . '/cmp' . $campaign->campaign_uid;
            $canSaveTemplate = true;

            if (file_exists($oldCampaignFilesPath) && is_dir($oldCampaignFilesPath)) {
                if (!mkdir($newCampaignFilesPath)) {
                    $canSaveTemplate = false;
                }

                if ($canSaveTemplate && !FileSystemHelper::copyOnlyDirectoryContents($oldCampaignFilesPath, $newCampaignFilesPath)) {
                    $canSaveTemplate = false;
                }
            }

            if (!$canSaveTemplate) {
                throw new Exception($this->t('Campaign template could not be saved while copying campaign!'));
            }

            $template->content = (string)str_replace('cmp' . $this->campaign_uid, 'cmp' . $campaign->campaign_uid, (string)$template->content);

            if (!$template->save(false)) {
                if (file_exists($newCampaignFilesPath) && is_dir($newCampaignFilesPath)) {
                    FileSystemHelper::deleteDirectoryContents($newCampaignFilesPath, true, 1);
                }
                throw new Exception($template->shortErrors->getAllAsString());
            }

            // template click actions
            if (!empty($templateClickActions) || !empty($templateClickActionsListFields)) {
                $templateUrls = $template->getContentUrls();
                foreach ($templateClickActions as $clickAction) {
                    if (!in_array($clickAction->url, $templateUrls)) {
                        continue;
                    }
                    $clickAction = clone $clickAction;
                    $clickAction->setIsNewRecord(true);
                    $clickAction->url_id       = null;
                    $clickAction->campaign_id  = (int)$campaign->campaign_id;
                    $clickAction->template_id  = (int)$template->template_id;
                    $clickAction->date_added   = MW_DATETIME_NOW;
                    $clickAction->last_updated = MW_DATETIME_NOW;
                    $clickAction->save(false);
                }
                foreach ($templateClickActionsListFields as $clickAction) {
                    if (!in_array($clickAction->url, $templateUrls)) {
                        continue;
                    }
                    $clickAction = clone $clickAction;
                    $clickAction->setIsNewRecord(true);
                    $clickAction->url_id       = null;
                    $clickAction->campaign_id  = (int)$campaign->campaign_id;
                    $clickAction->template_id  = (int)$template->template_id;
                    $clickAction->date_added   = MW_DATETIME_NOW;
                    $clickAction->last_updated = MW_DATETIME_NOW;
                    $clickAction->save(false);
                }
            }

            // delivery servers - start
            if (!empty($this->deliveryServers)) {
                foreach ($this->deliveryServers as $server) {
                    $campaignToServer = new CampaignToDeliveryServer();
                    $campaignToServer->server_id    = (int)$server->server_id;
                    $campaignToServer->campaign_id  = (int)$campaign->campaign_id;
                    $campaignToServer->save();
                }
            }
            // delivery servers - end

            // suppression lists - start
            $suppressionLists = CustomerSuppressionListToCampaign::model()->findAllByAttributes([
                'campaign_id' => $this->campaign_id,
            ]);
            if (!empty($suppressionLists)) {
                foreach ($suppressionLists as $suppressionList) {
                    $suppressionListToCampaign = new CustomerSuppressionListToCampaign();
                    $suppressionListToCampaign->list_id     = (int)$suppressionList->list_id;
                    $suppressionListToCampaign->campaign_id = (int)$campaign->campaign_id;
                    $suppressionListToCampaign->save();
                }
            }
            // suppression lists - end

            // attachments - start
            if (!empty($this->attachments)) {
                $copiedAttachments = false;
                $attachmentsPath = (string)Yii::getPathOfAlias('root.frontend.assets.files.campaign-attachments');
                $oldAttachments  = $attachmentsPath . '/' . $this->campaign_uid;
                $newAttachments  = $attachmentsPath . '/' . $campaign->campaign_uid;
                if (file_exists($oldAttachments) && is_dir($oldAttachments) && mkdir($newAttachments, 0777, true)) {
                    $copiedAttachments = FileSystemHelper::copyOnlyDirectoryContents($oldAttachments, $newAttachments);
                }
                if ($copiedAttachments) {
                    foreach ($this->attachments as $attachment) {
                        /** @var CampaignAttachment $attachment */
                        $attachment = clone $attachment;
                        $attachment->setIsNewRecord(true);
                        $attachment->attachment_id  = null;
                        $attachment->campaign_id    = (int)$campaign->campaign_id;
                        $attachment->date_added     = '';
                        $attachment->last_updated   = '';
                        $attachment->save(false);
                    }
                }
            }
            // attachments - end

            // 1.3.8.8 - campaign opne/unopen filter
            $openUnopenFilters = CampaignFilterOpenUnopen::model()->findAllByAttributes([
                'campaign_id' => $this->campaign_id,
            ]);

            foreach ($openUnopenFilters as $openUnopenFilter) {
                $openUnopenFilter = clone $openUnopenFilter;
                $openUnopenFilter->setIsNewRecord(true);
                $openUnopenFilter->campaign_id = (int)$campaign->campaign_id;
                $openUnopenFilter->save(false);
            }
            //

            if ($transaction) {
                $transaction->commit();
            }
            $copied = $campaign;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            if ($transaction) {
                $transaction->rollback();
            }
        }

        // 1.3.6.2
        hooks()->doAction('copy_campaign', new CAttributeCollection([
            'campaign' => $this,
            'copied'   => $copied,
        ]));

        return $copied;
    }

    /**
     * @return array
     */
    public function getListsDropDownArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $criteria->order = 'name ASC';

        // since 1.5.0
        $criteria = hooks()->applyFilters('campaign_model_get_lists_dropdown_array_criteria', $criteria, $this);

        return $_options = ListsCollection::findAll($criteria)->mapWithKeys(function (Lists $list) {
            return [$list->list_id => $list->name];
        })->all();
    }

    /**
     * @return array
     */
    public function getSegmentsDropDownArray(): array
    {
        $_options = [];

        if (empty($this->list_id)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->addNotInCondition('t.status', [ListSegment::STATUS_PENDING_DELETE]);
        $criteria->order = 't.name ASC';

        // since 1.5.0
        $criteria = hooks()->applyFilters(
            'campaign_model_get_segments_dropdown_array_criteria',
            $criteria,
            $this
        );

        return $_options = ListSegmentCollection::findAll($criteria)->mapWithKeys(function (ListSegment $segment) {
            return [$segment->segment_id => $segment->name];
        })->all();
    }

    /**
     * @return array
     */
    public function getGroupsDropDownArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->order = 'name ASC';

        // since 1.5.0
        $criteria = hooks()->applyFilters(
            'campaign_model_get_groups_dropdown_array_criteria',
            $criteria,
            $this
        );

        $models = CampaignGroup::model()->findAll($criteria);

        foreach ($models as $model) {
            $_options[$model->group_id] = $model->name;
        }

        return $_options;
    }

    /**
     * @return array
     */
    public function getSendGroupsDropDownArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->order = 'name ASC';

        // since 1.5.0
        $criteria = hooks()->applyFilters(
            'campaign_model_get_send_groups_dropdown_array_criteria',
            $criteria,
            $this
        );

        $models = CampaignSendGroup::model()->findAll($criteria);

        foreach ($models as $model) {
            $_options[$model->group_id] = $model->name;
        }

        return $_options;
    }

    /**
     * @return bool
     */
    public function getRemovable(): bool
    {
        $statuses = [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PENDING_SENDING,
            self::STATUS_PAUSED,
            self::STATUS_PENDING_DELETE,
            self::STATUS_BLOCKED,
            self::STATUS_PENDING_APPROVE,
        ];
        $removable = in_array($this->status, $statuses);

        if ($removable && !empty($this->customer_id) && !empty($this->customer)) {
            $removable = $this->customer->getGroupOption('campaigns.can_delete_own_campaigns', 'yes') === 'yes';
        }

        return $removable;
    }

    /**
     * @return bool
     */
    public function getEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_SENDING, self::STATUS_PAUSED]);
    }

    /**
     * @return bool
     */
    public function getAccessOverview(): bool
    {
        return !in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_SENDING]);
    }

    /**
     * @return bool
     */
    public function getCanBePaused(): bool
    {
        return in_array($this->status, [self::STATUS_SENDING, self::STATUS_PROCESSING, self::STATUS_PENDING_SENDING]);
    }

    /**
     * @return bool
     * @throws CException
     */
    public function getIsPaused(): bool
    {
        // 1.4.5
        if (is_cli() && app_param('campaign.delivery.sending.check_paused_realtime', true)) {
            $count = db()
                ->createCommand('SELECT COUNT(*) FROM {{campaign}} WHERE `campaign_id` = :cid AND `status` = :s')
                ->queryScalar([
                    ':cid' => (int)$this->campaign_id,
                    ':s'   => self::STATUS_PAUSED,
                ]);

            if ($count) {
                $this->status = self::STATUS_PAUSED;
            }
        }

        return in_array($this->status, [self::STATUS_PAUSED]);
    }

    /**
     * @return bool
     */
    public function getCanBeResumed(): bool
    {
        return in_array($this->status, [self::STATUS_PROCESSING]);
    }

    /**
     * @return bool
     */
    public function getCanBeMarkedAsSent(): bool
    {
        $statuses = [
            self::STATUS_BLOCKED,
            self::STATUS_PENDING_APPROVE,
            self::STATUS_PROCESSING,
            self::STATUS_PAUSED,
            self::STATUS_PENDING_SENDING,
        ];
        return in_array($this->status, $statuses);
    }

    /**
     * @return bool
     */
    public function getCanBeBlocked(): bool
    {
        return !in_array($this->status, [self::STATUS_BLOCKED, self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    /**
     * @return bool
     */
    public function getCanBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_APPROVE]);
    }

    /**
     * @return bool
     */
    public function getCanViewWebVersion(): bool
    {
        return !in_array($this->status, [self::STATUS_PENDING_APPROVE]);
    }

    /**
     * @return bool
     */
    public function getIsProcessing(): bool
    {
        return $this->getStatusIs(self::STATUS_PROCESSING);
    }

    /**
     * @return bool
     */
    public function getIsSending(): bool
    {
        return $this->getStatusIs(self::STATUS_SENDING);
    }

    /**
     * @return bool
     */
    public function getIsPendingApprove(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_APPROVE);
    }

    /**
     * @return bool
     */
    public function getIsPendingSending(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_SENDING);
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_DELETE);
    }

    /**
     * @return bool
     */
    public function getIsDraft(): bool
    {
        return $this->getStatusIs(self::STATUS_DRAFT);
    }

    /**
     * @return bool
     */
    public function getIsSent(): bool
    {
        return $this->getStatusIs(self::STATUS_SENT);
    }

    /**
     * @return bool
     */
    public function getIsBlocked(): bool
    {
        return $this->getStatusIs(self::STATUS_BLOCKED);
    }

    /**
     * @return array
     */
    public function getBlockedReasons(): array
    {
        if (!$this->getIsBlocked()) {
            return [];
        }
        $reasons = [];
        if ($bw = $this->getSubjectBlacklistWords()) {
            $reasons[] = $this->t('Campaign subject matched following blacklisted words: {words}', [
                '{words}' => implode(', ', $bw),
            ]);
        }
        if ($bw = $this->getContentBlacklistWords()) {
            $reasons[] = $this->t('Campaign content matched following blacklisted words: {words}', [
                '{words}' => implode(', ', $bw),
            ]);
        }
        if (empty($reasons)) {
            $reasons[] = $this->t('The campaign has been blocked by an administrator!');
        }
        return $reasons;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getSendAt(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDateTime($this->send_at);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getStartedAt(): string
    {
        if (empty($this->started_at) || (string)$this->started_at === '0000-00-00 00:00:00') {
            return '';
        }
        return $this->dateTimeFormatter->formatLocalizedDateTime($this->started_at);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFinishedAt(): string
    {
        if (empty($this->finished_at) || (string)$this->finished_at === '0000-00-00 00:00:00') {
            return '';
        }
        return $this->dateTimeFormatter->formatLocalizedDateTime($this->finished_at);
    }

    /**
     * @return string
     */
    public function getLastOpen(): string
    {
        if ($this->getIsNewRecord()) {
            return '';
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, date_added';
        $criteria->compare('campaign_id', $this->campaign_id);
        $criteria->order = 'id DESC';
        $criteria->limit = 1;

        $lastOpen = CampaignTrackOpen::model()->find($criteria);
        if (empty($lastOpen)) {
            return '';
        }

        return $lastOpen->dateAdded;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->campaign_uid;
    }

    /**
     * @return array
     */
    public function getSubjectToNameAvailableTags(): array
    {
        $tags = [];

        if (!empty($this->list)) {
            $fields = $this->list->fields;
            foreach ($fields as $field) {
                $tags[] = ['tag' => '[' . $field->tag . ']', 'required' => false];
            }
        }

        return CMap::mergeArray($tags, [
            ['tag' => '[LIST_NAME]', 'required' => false],
            ['tag' => '[RANDOM_CONTENT:a|b|c]', 'required' => false],
            ['tag' => '[REMOTE_CONTENT url=\'https://www.google.com/\']', 'required' => false],

            ['tag' => '[CURRENT_YEAR]', 'required' => false],
            ['tag' => '[CURRENT_MONTH]', 'required' => false],
            ['tag' => '[CURRENT_DAY]', 'required' => false],
            ['tag' => '[CURRENT_DATE]', 'required' => false],
            ['tag' => '[CURRENT_MONTH_FULL_NAME]', 'required' => false],

            ['tag' => '[SIGN_LT]', 'required' => false],
            ['tag' => '[SIGN_LTE]', 'required' => false],
            ['tag' => '[SIGN_GT]', 'required' => false],
            ['tag' => '[SIGN_GTE]', 'required' => false],
        ]);
    }

    /**
     * @return string
     */
    public function getDateTimeFormat(): string
    {
        $locale = app()->getLocale();
        $searchReplace = [
            '{1}' => $locale->getDateFormat('short'),
            '{0}' => $locale->getTimeFormat('short'),
        ];

        return (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $locale->getDateTimeFormat());
    }

    /**
     * @return string
     * @throws CException
     */
    public function getReloadedStatus(): string
    {
        $campaign = db()->createCommand()
            ->select('status')
            ->from($this->tableName())
            ->where('campaign_id = :cid', [':cid' => (int)$this->campaign_id])
            ->queryRow();

        return !empty($campaign['status']) ? (string)$campaign['status'] : '';
    }

    /**
     * @return array
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_DRAFT              => ucfirst($this->t(self::STATUS_DRAFT)),
            self::STATUS_PENDING_SENDING    => ucfirst($this->t(self::STATUS_PENDING_SENDING)),
            self::STATUS_PENDING_APPROVE    => ucfirst($this->t(self::STATUS_PENDING_APPROVE)),
            self::STATUS_SENDING            => ucfirst($this->t(self::STATUS_SENDING)),
            self::STATUS_SENT               => ucfirst($this->t(self::STATUS_SENT)),
            self::STATUS_PROCESSING         => ucfirst($this->t(self::STATUS_PROCESSING)),
            self::STATUS_PAUSED             => ucfirst($this->t(self::STATUS_PAUSED)),
            self::STATUS_BLOCKED            => ucfirst($this->t(self::STATUS_BLOCKED)),
            //self::STATUS_PENDING_DELETE   => ucfirst($this->>t(self::STATUS_PENDING_DELETE)),
        ];
    }

    /**
     * @return string
     */
    public function getStatusWithStats(): string
    {
        return (string)hooks()->applyFilters('campaign_get_status_with_stats', $this->_getStatusWithStats(), $this);
    }

    /**
     * @return array
     */
    public function getTypesList(): array
    {
        $types = [
            self::TYPE_REGULAR => ucfirst($this->t(self::TYPE_REGULAR)),
        ];

        $canUseAutoresponders = true;
        if (!empty($this->customer_id) && !empty($this->customer)) {
            $canUseAutoresponders = $this->customer->getGroupOption('campaigns.can_use_autoresponders', 'yes') === 'yes';
        }

        if ($canUseAutoresponders) {
            $types[self::TYPE_AUTORESPONDER] = ucfirst($this->t(self::TYPE_AUTORESPONDER));
        }

        return (array)hooks()->applyFilters('campaign_get_types_list', $types);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getTypeName(string $type = ''): string
    {
        if (empty($type)) {
            $type = $this->type;
        }
        return $this->getTypesList()[$type] ?? $type;
    }

    /**
     * @param string $type
     * @param string $lineBreak
     *
     * @return string
     */
    public function getTypeNameDetails(string $type = '', string $lineBreak = '<br />'): string
    {
        $type = $this->getTypeName($type);
        if (!$this->getIsAutoresponder()) {
            return $type;
        }
        if (empty($this->option)) {
            return $type;
        }

        $timeUnit = $this->option->autoresponder_time_unit;
        if ($this->option->autoresponder_time_value > 1) {
            $timeUnit .= 's';
        }
        $timeUnit = t('app', $timeUnit);

        return sprintf(
            '%s%s(%d %s/%s)',
            $type,
            $lineBreak,
            $this->option->autoresponder_time_value,
            $timeUnit,
            $this->option->getAutoresponderEventName()
        );
    }

    /**
     * @return bool
     */
    public function getIsAutoresponder(): bool
    {
        return (string)$this->type === self::TYPE_AUTORESPONDER;
    }

    /**
     * @return bool
     */
    public function getIsRegular(): bool
    {
        return (string)$this->type === self::TYPE_REGULAR;
    }

    /**
     * @return string
     */
    public function getListSegmentName(): string
    {
        static $names  = [];
        if (isset($names[$this->campaign_id])) {
            return $names[$this->campaign_id];
        }

        $name   = [];
        $name[] = (empty($this->segment_id) ? $this->list->name : $this->list->name . '/' . $this->segment->name);

        return $names[$this->campaign_id] = implode(', ', $name);
    }

    /**
     * @return string
     */
    public function getSuppressionListsName(): string
    {
        static $suppressionNames  = [];
        if (isset($suppressionNames[$this->campaign_id])) {
            return $suppressionNames[$this->campaign_id];
        }

        // suppression lists
        $suppressionLists = CustomerSuppressionListToCampaign::model()->findAllByAttributes([
            'campaign_id' => $this->campaign_id,
        ]);

        $name   = [];
        /** @var CustomerSuppressionListToCampaign $suppressionList */
        foreach ($suppressionLists as $suppressionList) {
            $name[] = $suppressionList->suppressionList->name;
        }

        return $suppressionNames[$this->campaign_id] = implode(', ', $name);
    }

    /**
     * @return string
     */
    public function getDeliveryServersNames(): string
    {
        static $deliveryServersNames  = [];
        if (isset($deliveryServersNames[$this->campaign_id])) {
            return $deliveryServersNames[$this->campaign_id];
        }

        // assigned delivery servers
        $deliveryServers = $this->deliveryServers;

        $name   = [];
        /** @var DeliveryServer $deliveryServer */
        foreach ($deliveryServers as $deliveryServer) {
            $name[] = $deliveryServer->name;
        }

        return $deliveryServersNames[$this->campaign_id] = implode(', ', $name);
    }


    /**
     * @return int
     */
    public function countForwards(): int
    {
        return (int)CampaignForwardFriend::model()->countByAttributes(['campaign_id' => $this->campaign_id]);
    }

    /**
     * @return int
     */
    public function countAbuseReports(): int
    {
        return (int)CampaignAbuseReport::model()->countByAttributes(['campaign_id' => $this->campaign_id]);
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function saveStatus(string $status = ''): bool
    {
        if (empty($this->campaign_id)) {
            return false;
        }

        if ($status && (string)$status === $this->status) {
            return true;
        }

        $mutexKey = __METHOD__ . ':' . (int)$this->campaign_id;
        if (!mutex()->acquire($mutexKey, 5)) {
            return false;
        }

        $result = false;

        try {
            if ($status) {
                $this->status = $status;
            }

            $attributes = ['status' => $this->status];
            $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;

            if ($this->getStatusIs(self::STATUS_SENT)) {
                $this->finished_at = $attributes['finished_at'] = MW_DATETIME_NOW;
            }

            if ($this->getStatusIs(self::STATUS_PROCESSING) && !$this->getStartedAt()) {
                $this->started_at = $attributes['started_at'] = MW_DATETIME_NOW;
            }

            // 1.7.9
            hooks()->doAction($this->buildHookName(['suffix' => 'before_savestatus']), $this);
            //

            $result = (bool)db()->createCommand()->update(
                $this->tableName(),
                $attributes,
                'campaign_id = :cid',
                [':cid' => (int)$this->campaign_id]
            );

            // 1.7.9
            hooks()->doAction($this->buildHookName(['suffix' => 'after_savestatus']), $this, $result);
            //
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($mutexKey);

        return (bool)$result;
    }

    /**
     * @param string|CDbExpression $sendAt
     *
     * @return bool
     */
    public function saveSendAt($sendAt = ''): bool
    {
        if (empty($this->campaign_id)) {
            return false;
        }

        if ($sendAt) {
            $this->send_at = $sendAt;
        }
        $attributes = ['send_at' => $this->send_at];

        return (bool)db()->createCommand()->update(
            $this->tableName(),
            $attributes,
            'campaign_id = :cid',
            [':cid' => (int)$this->campaign_id]
        );
    }

    /**
     * @return bool
     */
    public function getIsRecurring(): bool
    {
        return !empty($this->campaign_id) &&
               $this->getIsRegular() &&
               !empty($this->option) &&
               !empty($this->option->cronjob) &&
               !empty($this->option->cronjob_enabled);
    }

    /**
     * @return string
     */
    public function getRecurringCronjob(): string
    {
        return $this->getIsRecurring() ? $this->option->cronjob : '';
    }

    /**
     * @since 1.9.22 - Mutex and rescheduled check added
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    public function tryReschedule(): bool
    {
        if (!$this->getIsRecurring()) {
            return false;
        }

        // since 2.0.29
        if ($this->customer->getGroupOption('campaigns.can_use_recurring_campaigns', 'yes') !== self::TEXT_YES) {
            return false;
        }

        $mutexKey = sprintf('%s:%d', __METHOD__, (int)$this->campaign_id);
        if (!mutex()->acquire($mutexKey)) {
            return false;
        }

        // make sure we get fresh data here
        // TODO: Is this really needed?
        $this->option->refresh();

        // Already rescheduled
        if ((string)$this->option->cronjob_rescheduled === self::TEXT_YES) {
            mutex()->release($mutexKey);
            return false;
        }

        // check it
        $cronjobMaxRuns     = $this->option->cronjob_max_runs;
        $cronjobRunsCounter = $this->option->cronjob_runs_counter;

        if ($cronjobMaxRuns > -1 && $cronjobRunsCounter >= $cronjobMaxRuns) {
            mutex()->release($mutexKey);
            return false;
        }

        if (!($campaign = $this->copy())) {
            mutex()->release($mutexKey);
            return false;
        }

        // update it
        $campaign->option->cronjob_runs_counter = $cronjobRunsCounter + 1;
        CampaignOption::model()->updateByPk((int)$campaign->campaign_id, [
            'cronjob_runs_counter' => $campaign->option->cronjob_runs_counter,
        ]);

        // check it again
        $cronjobMaxRuns     = $campaign->option->cronjob_max_runs;
        $cronjobRunsCounter = $campaign->option->cronjob_runs_counter;

        if ($cronjobMaxRuns > -1 && $cronjobRunsCounter >= $cronjobMaxRuns) {
            // since 1.9.32
            $campaign->saveStatus(self::STATUS_PENDING_DELETE);

            mutex()->release($mutexKey);
            return false;
        }

        try {

            // since 1.9.32
            // In the past, we calculated the next occurrence based on the current campaign send time ($this->send_at)
            // which was a mistake because campaigns send time would always be in the past relative to the current time.
            // Using the current time makes most sense, so instead of using Campaign::send_at as reference, we use 'now', the current time.
            $currentTime       = new DateTime('now', new DateTimeZone(app()->getTimeZone()));
            $cron              = new Cron\CronExpression($this->option->cronjob);
            $campaign->send_at = $cron->getNextRunDate($currentTime)->format('Y-m-d H:i:s');

            // The cron frequency is always relative to the customer timezone
            // Because of this we need to convert between the timezones (UTC and customer)
            if (!empty($this->customer->timezone)) {
                $customerTime = clone $currentTime;
                $customerTime->setTimezone(new DateTimeZone($this->customer->timezone));

                $cron = new Cron\CronExpression($this->option->cronjob);
                $campaign->send_at = $cron->getNextRunDate($customerTime)->format('Y-m-d H:i:s');

                $currentTime = new DateTime($campaign->send_at, new DateTimeZone($this->customer->timezone));
                $currentTime->setTimezone(new DateTimeZone(app()->getTimeZone()));

                $campaign->send_at = $currentTime->format('Y-m-d H:i:s');
            }

            $campaign->status  = self::STATUS_SENDING;
            $attributes = [
                'send_at' => $campaign->send_at,
                'status'  => $campaign->status,
            ];
            $ok = (bool)db()->createCommand()->update(
                $this->tableName(),
                $attributes,
                'campaign_id = :cid',
                [':cid' => (int)$campaign->campaign_id]
            );
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            $ok = false;
        }

        // since 1.9.22
        if ($ok) {
            $this->option->cronjob_rescheduled = self::TEXT_YES;
            $attributes = [
                'cronjob_rescheduled' => self::TEXT_YES,
            ];
            try {
                db()->createCommand()->update(
                    $this->option->tableName(),
                    $attributes,
                    'campaign_id = :cid',
                    [':cid' => (int)$this->campaign_id]
                );
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                $ok = false;
            }
        }

        // since 1.9.32
        if (!$ok) {
            $campaign->saveStatus(self::STATUS_PENDING_DELETE);
        }

        mutex()->release($mutexKey);
        return $ok;
    }

    /**
     * @return bool
     */
    public function getDeliveryLogsArchived(): bool
    {
        return (string)$this->delivery_logs_archived === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getTotalDeliveryTime(): string
    {
        if (
            empty($this->started_at) ||
            empty($this->finished_at) ||
            ($startedAt = (int)strtotime((string)$this->started_at)) === ($finishedAt = (int)strtotime((string)$this->finished_at))
        ) {
            return $this->t('N/A');
        }

        return DateTimeHelper::timespan($startedAt, $finishedAt);
    }

    /**
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return int
     * @throws CDbException
     */
    public function countSubscribers(CDbCriteria $mergeCriteria = null): int
    {
        if (!empty($this->segment_id)) {
            $count = $this->countSubscribersByListSegment($mergeCriteria);
        } else {
            $count = $this->countSubscribersByList($mergeCriteria);
        }

        return (int)$count;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return array
     * @throws CDbException
     */
    public function findSubscribers(int $offset = 0, int $limit = 100, CDbCriteria $mergeCriteria = null): array
    {
        if (!empty($this->segment_id)) {
            $subscribers = $this->findSubscribersByListSegment($offset, $limit, $mergeCriteria);
        } else {
            $subscribers = $this->findSubscribersByList($offset, $limit, $mergeCriteria);
        }
        return $subscribers;
    }

    /**
     * @return array
     */
    public function getBulkActionsList(): array
    {
        $actions = [
            self::BULK_ACTION_DELETE              => t('app', 'Delete'),
            self::BULK_ACTION_COPY                => t('app', 'Copy'),
            self::BULK_ACTION_PAUSE_UNPAUSE       => t('app', 'Pause/Unpause'),
            self::BULK_ACTION_MARK_SENT           => t('app', 'Mark as sent'),
            self::BULK_ACTION_COMPARE_CAMPAIGNS   => t('app', 'Compare campaigns'),
        ];

        if (!empty($this->customer_id)) {
            $actions[self::BULK_ACTION_SEND_TEST_EMAIL]     = t('app', 'Send test email');
            $actions[self::BULK_ACTION_SHARE_CAMPAIGN_CODE] = t('app', 'Share with code');
        }

        if (
            !empty($this->customer_id) &&
            !empty($this->customer) &&
            $this->customer->getGroupOption('campaigns.can_export_stats', 'yes') === 'yes'
        ) {
            $actions[self::BULK_EXPORT_BASIC_STATS] = t('app', 'Export basic stats');
        }

        // since 1.9.17
        $actions = (array)hooks()->applyFilters('campaign_model_get_bulk_actions_list', $actions, $this);

        return $actions;
    }

    /**
     * @return array
     */
    public function getSubjectBlacklistWords(): array
    {
        if (empty($this->subject)) {
            return [];
        }

        static $subjectWords;
        if ($subjectWords !== null && empty($subjectWords)) {
            return [];
        }
        if ($subjectWords === null || !is_array($subjectWords)) {
            $subjectWords = [];

            /** @var OptionCampaignBlacklistWords $optionCampaignBlacklistWords */
            $optionCampaignBlacklistWords = container()->get(OptionCampaignBlacklistWords::class);

            if ($optionCampaignBlacklistWords->getIsEnabled()) {
                $subjectWords = $optionCampaignBlacklistWords->getSubjectWords();
            }
        }
        if (empty($subjectWords)) {
            return [];
        }
        $found = [];
        foreach ($subjectWords as $word) {
            if (stripos($this->subject, $word) !== false) {
                $found[] = $word;
            }
        }
        return $found;
    }

    /**
     * @return array
     */
    public function getContentBlacklistWords(): array
    {
        if (empty($this->template)) {
            return [];
        }

        static $contentWords;
        if ($contentWords !== null && empty($contentWords)) {
            return [];
        }
        if ($contentWords === null || !is_array($contentWords)) {
            $contentWords = [];

            /** @var OptionCampaignBlacklistWords $optionCampaignBlacklistWords */
            $optionCampaignBlacklistWords = container()->get(OptionCampaignBlacklistWords::class);

            if ($optionCampaignBlacklistWords->getIsEnabled()) {
                $contentWords = $optionCampaignBlacklistWords->getContentWords();
            }
        }
        if (empty($contentWords)) {
            return [];
        }
        $found   = [];
        $content = strip_tags($this->template->content);
        foreach ($contentWords as $word) {
            if (stripos($content, $word) !== false) {
                $found[] = $word;
            }
        }
        if (empty($found) && !empty($this->template->plain_text)) {
            $content = $this->template->plain_text;
            foreach ($contentWords as $word) {
                if (stripos($content, $word) !== false) {
                    $found[] = $word;
                }
            }
        }
        return $found;
    }

    /**
     * @return bool
     */
    public function sendNotificationsForPendingApproveCampaign(): bool
    {
        if (!$this->getIsPendingApprove()) {
            return false;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        /** @var OptionUrl $url */
        $url = container()->get(OptionUrl::class);

        /** @var User[] $users */
        $users = User::model()->findAllByAttributes([
            'status' => User::STATUS_ACTIVE,
        ]);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'campaign-pending-approval',
            [
                'subject' => $this->t('A campaign requires approval before sending!'),
            ],
            [
                '[CAMPAIGN_OVERVIEW_URL]' => $url->getBackendUrl('campaigns/' . $this->campaign_uid . '/overview'),
            ]
        );

        foreach ($users as $user) {
            $_email = new TransactionalEmail();
            $_email->sendDirectly = false;
            $_email->to_name      = $user->getFullName();
            $_email->to_email     = $user->email;
            $_email->from_name    = $common->getSiteName();
            $_email->subject      = $params['subject'];
            $_email->body         = $params['body'];
            $_email->save();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function sendNotificationsForBlockedCampaign(): bool
    {
        if (!$this->getIsBlocked()) {
            return false;
        }

        /** @var OptionCampaignBlacklistWords $optionCampaignBlacklistWords */
        $optionCampaignBlacklistWords = container()->get(OptionCampaignBlacklistWords::class);

        if (!$optionCampaignBlacklistWords->getIsEnabled()) {
            return false;
        }

        $emails = $optionCampaignBlacklistWords->getNotificationsTo();
        if (empty($emails)) {
            return false;
        }

        if (empty($this->option) || empty($this->option->blocked_reason)) {
            return false;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        /** @var OptionUrl $url */
        $url = container()->get(OptionUrl::class);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'campaign-has-been-blocked',
            [
                'subject' => $this->t('A campaign has been blocked!'),
            ],
            [
                '[CAMPAIGN_OVERVIEW_URL]' => $url->getBackendUrl('campaigns/' . $this->campaign_uid . '/overview'),
                '[REASON]'                => str_replace('|', '<br />', $this->option->blocked_reason),
            ]
        );

        foreach ($emails as $email) {
            $_email = new TransactionalEmail();
            $_email->sendDirectly = false;
            $_email->to_name      = $email;
            $_email->to_email     = $email;
            $_email->from_name    = $common->getSiteName();
            $_email->subject      = $params['subject'];
            $_email->body         = $params['body'];
            $_email->save();
        }

        return true;
    }

    /**
     * @return bool
     * @throws CException
     */
    public function sendStatsEmail(): bool
    {
        if (empty($this->option->email_stats)) {
            return false;
        }

        $dsParams = [
            'useFor' => [DeliveryServer::USE_FOR_REPORTS],
        ];
        if (!($server = DeliveryServer::pickServer(0, $this, $dsParams))) {
            return false;
        }

        $viewData = [
            'campaign' => $this,
        ];

        if (is_cli()) {
            /** @var CConsoleApplication $app */
            $app      = app();
            $renderer = $app->getCommand();
        } else {
            /** @var CWebApplication $app */
            $app      = app();
            $renderer = $app->getController();
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'campaign-stats',
            [
                'subject' => t('campaign_reports', 'The campaign {name} has finished sending, here are the stats', ['{name}' => $this->name]),
            ],
            [
                '[CAMPAIGN_NAME]'         => $this->name,
                '[CAMPAIGN_OVERVIEW_URL]' => $optionUrl->getBackendUrl('campaigns/' . $this->campaign_uid . '/overview'),
                '[STATS_TABLE]'           => $renderer->renderFile((string)Yii::getPathOfAlias('console.views.campaign-stats') . '.php', $viewData, true),
            ]
        );

        $recipients = explode(',', $this->option->email_stats);
        $recipients = array_map('trim', $recipients);

        // because we don't have what to parse here!
        $hasDefaultFromName = strpos($this->from_name, '[') !== false && !empty($this->list->default->from_name);
        $fromName = $hasDefaultFromName ? $this->list->default->from_name : $this->from_name;

        $emailParams                    = [];
        $emailParams['fromNameCustom']  = $fromName;
        $emailParams['replyTo']         = [$this->reply_to => $fromName];
        $emailParams['subject']         = $params['subject'];
        $emailParams['body']            = $params['body'];

        foreach ($recipients as $recipient) {
            if (!FilterVarHelper::email($recipient)) {
                continue;
            }
            $emailParams['to']  = [$recipient => $fromName];
            $server
                ->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN)
                ->setDeliveryObject($this)
                ->sendEmail($emailParams);
        }

        return true;
    }

    /**
     * @param int $by
     */
    public function incrementPriority(int $by = 1): void
    {
        $this->updatePriority($this->priority + (int)$by);
    }

    /**
     * @param int $by
     */
    public function decrementPriority(int $by = 1): void
    {
        $this->updatePriority($this->priority - (int)$by);
    }

    /**
     * @param int $priority
     */
    public function updatePriority(int $priority = 0): void
    {
        if ((int)$this->priority === $priority) {
            return;
        }

        $this->priority = (int)$priority;
        $attributes = ['priority' => (int)$priority];

        db()->createCommand()->update(
            $this->tableName(),
            $attributes,
            'campaign_id = :cid',
            [':cid' => (int)$this->campaign_id]
        );
    }

    /**
     * @param string $attribute
     * @param array $params
     *
     * @throws CException
     */
    public function _validateEMailWithTag(string $attribute, array $params = []): void
    {
        $attributeValue = (string)($this->$attribute ?? '');
        if (empty($attributeValue)) {
            return;
        }

        if (strpos($attributeValue, '[') !== false && strpos($attributeValue, ']') !== false) {
            if (empty($this->list_id)) {
                $this->addError($attribute, $this->t('Please associate a list first!'));
                return;
            }
            $subscriber = ListSubscriber::model()->findByAttributes([
                'list_id' => $this->list_id,
                'status'  => ListSubscriber::STATUS_CONFIRMED,
            ]);
            if (empty($subscriber)) {
                $this->addError($attribute, $this->t('You need at least one subscriber in your selected list!'));
                return;
            }
            $tags = CampaignHelper::getCommonTagsSearchReplace($attributeValue, $this, $subscriber);
            $attr = (string)str_replace(array_keys($tags), array_values($tags), $attributeValue);

            if (!FilterVarHelper::email($attr)) {
                $this->addError(
                    $attribute,
                    $this->t('{attr} is not a valid email address (even after the tags were parsed).', [
                            '{attr}' => $this->getAttributeLabel($attribute),
                    ])
                );
                return;
            }
            return;
        }

        if (FilterVarHelper::email($attributeValue)) {
            return;
        }

        $this->addError($attribute, $this->t('{attr} is not a valid email address.', ['{attr}' => $this->getAttributeLabel($attribute)]));
    }

    /**
     * @param int $count
     */
    public function updateSendingGiveupCount(int $count = 0): void
    {
        $this->option->updateSendingGiveupCount($count);
    }

    /**
     * @return bool
     */
    public function getCanShowResetGiveupsButton(): bool
    {
        if (!$this->getIsSent()) {
            return false;
        }

        if (empty($this->option->giveup_count)) {
            return false;
        }

        $queued = CampaignResendGiveupQueue::model()->countByAttributes([
            'campaign_id' => $this->campaign_id,
        ]);

        if ($queued) {
            return false;
        }

        return true;
    }

    /**
     * @param int $by
     */
    public function updateSendingGiveupCounter(int $by = 1): void
    {
        $this->option->updateSendingGiveupCounter($by);
    }

    /**
     * @return int
     */
    public function getSendingGiveupsCount(): int
    {
        return (int)CampaignDeliveryLog::model()->countByAttributes([
            'campaign_id' => (int)$this->campaign_id,
            'status'      => CampaignDeliveryLog::STATUS_GIVEUP,
        ]);
    }

    /**
     * @return int
     */
    public function resetSendingGiveups(): int
    {
        return (int)CampaignDeliveryLog::model()->deleteAllByAttributes([
            'campaign_id' => (int)$this->campaign_id,
            'status'      => CampaignDeliveryLog::STATUS_GIVEUP,
        ]);
    }

    /**
     * @return CampaignStatsProcessorBehavior
     */
    public function getStats(): CampaignStatsProcessorBehavior
    {
        if (!$this->asa('statsBehavior')) {
            $this->attachBehavior('statsBehavior', [
                'class' => 'customer.components.behaviors.CampaignStatsProcessorBehavior',
            ]);
        }
        return $this->statsBehavior;
    }

    /**
     * @param bool $formatNumber
     * @return mixed
     */
    public function getHardBounceRate(bool $formatNumber = false)
    {
        return $this->getStats()->getHardBouncesRate($formatNumber);
    }

    /**
     * @param bool $formatNumber
     * @return mixed
     */
    public function getSoftBounceRate(bool $formatNumber = false)
    {
        return $this->getStats()->getSoftBouncesRate($formatNumber);
    }

    /**
     * @param bool $formatNumber
     * @return mixed
     */
    public function getUnsubscribesRate(bool $formatNumber = false)
    {
        return $this->getStats()->getUnsubscribesRate($formatNumber);
    }

    /**
     * @return string
     */
    public function getRegularOpenUnopenDisplayText(): string
    {
        if (!$this->getIsRegular()) {
            return '';
        }

        $models = CampaignFilterOpenUnopen::model()->findAllByAttributes([
            'campaign_id' => $this->campaign_id,
        ]);

        if (empty($models)) {
            return '';
        }

        $action = $this->t(CampaignFilterOpenUnopen::ACTION_OPEN);
        if ((string)$models[0]['action'] === CampaignFilterOpenUnopen::ACTION_UNOPEN) {
            $action = $this->t('not open');
        }

        $campaigns = [];
        foreach ($models as $model) {
            if ($model->previousCampaign->isPendingDelete) {
                continue;
            }
            /** @var Campaign $campaign */
            $campaign = Campaign::model()->findByPk($model->previous_campaign_id);
            $campaigns[] = CHtml::link($campaign->name, ['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        return $this->t('Subscribers that did {action} the campaigns: {campaigns}', [
            '{action}'    => $action,
            '{campaigns}' => implode(', ', $campaigns),
        ]);
    }

    /**
     * @return array
     */
    public function getRelatedCampaignsAsOptions(): array
    {
        if (empty($this->list_id)) {
            return [];
        }

        $_openRelatedCampaigns = [];

        $criteria = new CDbCriteria();
        $criteria->select = 't.campaign_id, t.name, t.type';
        $criteria->compare('t.list_id', $this->list_id);
        $criteria->addNotInCondition('t.status', [Campaign::STATUS_PENDING_DELETE]);
        $criteria->addCondition('t.campaign_id != :cid');
        $criteria->params[':cid'] = (int)$this->campaign_id;
        $criteria->order = 't.campaign_id DESC';

        /** @var Campaign[] $campaigns */
        $campaigns = Campaign::model()->findAll($criteria);

        foreach ($campaigns as $campaign) {
            if (empty($campaign->option)) {
                continue;
            }
            if ($campaign->getIsAutoresponder()) {
                $_openRelatedCampaigns[$campaign->campaign_id] = sprintf(
                    '%s (%s/%s)',
                    $campaign->name,
                    $campaign->getTypeName(),
                    $campaign->option->getAutoresponderEventName()
                );
            } else {
                $_openRelatedCampaigns[$campaign->campaign_id] = sprintf(
                    '%s (%s)',
                    $campaign->name,
                    $campaign->getTypeName()
                );
            }
        }

        return $_openRelatedCampaigns;
    }

    /**
     * @return string
     */
    public function getGridViewRecipients(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        return (string)$this->getStats()->getProcessedCount(true);
    }

    /**
     * @return string
     */
    public function getGridViewSent(): string
    {
        return $this->getGridViewRecipients();
    }

    /**
     * @return string
     */
    public function getGridViewDelivered(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        return $this->getStats()->getDeliverySuccessCount(true) . ' (' . $this->getStats()->getDeliverySuccessRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getGridViewOpens(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        if (empty($this->option) || $this->option->open_tracking !== CampaignOption::TEXT_YES) {
            return t('app', 'N/A');
        }

        return $this->getStats()->getUniqueOpensCount(true) . ' (' . $this->getStats()->getUniqueOpensRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getGridViewClicks(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        if (empty($this->option) || $this->option->url_tracking !== CampaignOption::TEXT_YES) {
            return t('app', 'N/A');
        }

        return $this->getStats()->getUniqueClicksCount(true) . ' (' . $this->getStats()->getUniqueClicksRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getGridViewBounces(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        return $this->getStats()->getBouncesCount(true) . ' (' . $this->getStats()->getBouncesRate(true) . '%)';
    }

    /**
     * @return string
     */
    public function getGridViewUnsubs(): string
    {
        if (in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_DELETE])) {
            return t('app', 'N/A');
        }

        return $this->getStats()->getUnsubscribesCount(true) . ' (' . $this->getStats()->getUnsubscribesRate(true) . '%)';
    }

    /**
     * @return bool
     */
    public function markAsSent(): bool
    {
        if (!$this->getCanBeMarkedAsSent()) {
            return false;
        }
        return (bool)$this->saveStatus(Campaign::STATUS_SENT);
    }

    /**
     * @return bool
     * @throws CDbException
     */
    public function postpone(): bool
    {
        if (empty($this->customer_id) || empty($this->customer)) {
            return false;
        }

        if ((int)$this->priority > 24) {
            $interval = 'DATE_ADD(NOW(), INTERVAL 1 DAY)';
        } else {
            $interval = 'DATE_ADD(NOW(), INTERVAL 1 HOUR)';
        }

        return $this->saveAttributes([
            'status'        => Campaign::STATUS_SENDING,
            'last_updated'  => MW_DATETIME_NOW,
            'send_at'       => new CDbExpression($interval),
            'priority'      => new CDbExpression('priority + 1'),
        ]);
    }

    /**
     * @return bool
     * @throws CDbException
     * @throws Exception
     * @since 1.9.5
     */
    public function postponeBecauseCustomerReachedQuota(): bool
    {
        if (empty($this->customer_id) || empty($this->customer)) {
            return false;
        }

        if (!$this->postpone()) {
            return false;
        }

        // each campaign has to have it's own key connected to the quota mark so that
        // we only issue a single notification for each campaign per quota mark
        $quotaMark  = $this->customer->getLastQuotaMark();
        $optionsKey = sprintf('system.customers.campaigns.postpone_key_%d_%s.notification', $quotaMark->mark_id, $this->campaign_uid);
        if (!options()->get($optionsKey)) {
            options()->set($optionsKey, true);

            $this->refresh();

            /** @var OptionUrl $optionUrl */
            $optionUrl = container()->get(OptionUrl::class);

            // create the message
            $message = new CustomerMessage();
            $message->customer_id = (int)$this->customer_id;
            $message->title       = 'One of your campaigns has been postponed because you have reached your quota!';
            $message->message     = 'The campaign {campaign} has been postponed until {date} because you have reached your assigned quota!';
            $message->message_translation_params = [
                '{campaign}' => CHtml::link($this->name, $optionUrl->getCustomerUrl('campaigns/' . $this->campaign_uid . '/overview')),
                '{date}'     => $this->getSendAt(),
            ];
            $message->save();
        }

        return true;
    }

    /**
     * @see https://github.com/onetwist-software/mailwizz/issues/585
     *
     * @return bool
     */
    public function getCanUseQueueTable(): bool
    {
        if (!app_param('send.campaigns.command.useTempQueueTables', false)) {
            return false;
        }

        if ($this->getIsAutoresponder() && !empty($this->segment_id)) {
            return false;
        }

        return true;
    }

    /**
     * This is mainly used because of A/B Testing, to avoid changing the original subject of the campaign
     *
     * @param string $subject
     */
    public function setCurrentSubject(string $subject): void
    {
        $this->_currentSubject = $subject;
    }

    /**
     * This is mainly used because of A/B Testing, to avoid changing the original subject of the campaign
     *
     * @return string
     */
    public function getCurrentSubject(): string
    {
        if (empty($this->_currentSubject)) {
            return (string)$this->subject;
        }
        return (string)$this->_currentSubject;
    }

    /**
     * @return bool
     */
    public function getCanDoAbTest(): bool
    {
        return !$this->getIsNewRecord() && $this->getIsAutoresponder();
    }

    /**
     * @return null|CampaignAbtestSubject
     */
    public function pickAbTestSubject(): ?CampaignAbtestSubject
    {
        if (!$this->getCanDoAbTest()) {
            return null;
        }

        static $abTestWinnerSubject = [];
        if (array_key_exists((int)$this->campaign_id, $abTestWinnerSubject) || !empty($abTestWinnerSubject[(int)$this->campaign_id])) {
            return $abTestWinnerSubject[(int)$this->campaign_id];
        }

        static $abTestCampaigns = [];
        if (isset($abTestCampaigns[(int)$this->campaign_id]) && !$abTestCampaigns[(int)$this->campaign_id]) {
            return null;
        }

        if (!mutex()->acquire($this->getAbtestDataMutexKey(), 10)) {
            return null;
        }

        try {
            /** @var CampaignAbtest|null $abTest */
            $abTest = CampaignAbtest::model()->findByAttributes([
                'campaign_id'   => (int)$this->campaign_id,
                'enabled'       => CampaignAbtest::TEXT_YES,
                'status'        => [CampaignAbtest::STATUS_ACTIVE, CampaignAbtest::STATUS_COMPLETE],
            ]);
            $abTestCampaigns[(int)$this->campaign_id] = !empty($abTest);

            if (empty($abTest)) {
                return null;
            }

            // winner already designated
            // do a query and return the right subject so that when using pcntl all the other processes will get this subject not the
            // one loaded when they loaded the campaign before the winner designated
            if ($abTest->getIsComplete()) {
                return $abTestWinnerSubject[(int)$this->campaign_id] = CampaignAbtestSubject::model()->findByAttributes([
                    'test_id'   => $abTest->test_id,
                    'status'    => CampaignAbtestSubject::STATUS_ACTIVE,
                    'is_winner' => CampaignAbtestSubject::TEXT_YES,
                ]);
            }

            $criteria = new CDbCriteria();
            $criteria->compare('test_id', (int)$abTest->test_id);
            $criteria->compare('status', CampaignAbtestSubject::STATUS_ACTIVE);
            $criteria->order = 'usage_count ASC';

            $subject = CampaignAbtestSubject::model()->find($criteria);
            if (empty($subject)) {
                return null;
            }

            if (!$this->abTestSubjectReachedLimit($abTest, $subject)) {
                $subject->incrementUsageCount();
            }

            return $subject;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        } finally {
            mutex()->release($this->getAbtestDataMutexKey());
        }

        return null;
    }

    /**
     * @param CampaignTrackOpen $trackOpen
     *
     * @return bool
     */
    public function updateAbTestSubjectOpensCountFromTrackOpen(CampaignTrackOpen $trackOpen): bool
    {
        if (!$this->getCanDoAbTest()) {
            return false;
        }

        static $abTestCampaigns = [];
        if (isset($abTestCampaigns[(int)$this->campaign_id]) && !$abTestCampaigns[(int)$this->campaign_id]) {
            return false;
        }

        if (!mutex()->acquire($this->getAbtestDataMutexKey(), 10)) {
            return false;
        }

        try {
            /** @var CampaignAbtest|null $abTest */
            $abTest = CampaignAbtest::model()->findByAttributes([
                'campaign_id'   => (int)$this->campaign_id,
                'enabled'       => CampaignAbtest::TEXT_YES,
                'status'        => CampaignAbtest::STATUS_ACTIVE,
            ]);
            $abTestCampaigns[(int)$this->campaign_id] = !empty($abTest);

            if (empty($abTest)) {
                return false;
            }

            $criteria = new CDbCriteria();
            $criteria->select = 'log_id';
            $criteria->compare('campaign_id', $this->campaign_id);
            $criteria->compare('subscriber_id', $trackOpen->subscriber_id);

            $deliveryLog = CampaignDeliveryLog::model()->find($criteria);
            if (empty($deliveryLog)) {
                return false;
            }

            $subjectToDeliveryLog = CampaignAbtestSubjectToDeliveryLog::model()->findByAttributes([
                'log_id' => (int)$deliveryLog->log_id,
            ]);
            if (empty($subjectToDeliveryLog)) {
                return false;
            }

            $subject = CampaignAbtestSubject::model()->findByAttributes([
                'subject_id' => $subjectToDeliveryLog->subject_id,
                'status'     => CampaignAbtestSubject::STATUS_ACTIVE,
            ]);
            if (empty($subject)) {
                return false;
            }

            if ($this->abTestSubjectReachedLimit($abTest, $subject)) {
                return false;
            }

            $subjectToOpen = new CampaignAbtestSubjectToTrackOpen();
            $subjectToOpen->subject_id = (int)$subject->subject_id;
            $subjectToOpen->open_id    = (int)$trackOpen->id;
            $subjectToOpen->save();

            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.subscriber_id))';
            $criteria->compare('t.campaign_id', $this->campaign_id);
            $criteria->compare('cto.subject_id', $subject->subject_id);
            $criteria->join = sprintf(
                'INNER JOIN %s cto ON cto.open_id = t.id',
                (new CampaignAbtestSubjectToTrackOpen())->tableName()
            );

            $subject->saveOpensCount((int)CampaignTrackOpen::model()->count($criteria));

            return $this->abTestSubjectReachedLimit($abTest, $subject);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        } finally {
            mutex()->release($this->getAbtestDataMutexKey());
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getAbtestDataMutexKey(): string
    {
        return sha1(sprintf('data:abtest:campaign:%d', $this->campaign_id));
    }

    /**
     * This should be only called from Campaign::updateAbTestSubjectOpensCountWithSubscriber and Campaign::pickAbTestSubject
     * This is not thread safe, it must be called from a method already protected by mutex
     *
     * @param CampaignAbtest $abTest
     * @param CampaignAbtestSubject $subject
     *
     * @return bool
     * @throws CDbException
     */
    protected function abTestSubjectReachedLimit(CampaignAbtest $abTest, CampaignAbtestSubject $subject): bool
    {
        // in case one of the counters are reached before the entire test is completed
        $abTestSaveAttributes = [];

        $reachedMaxOpens = false;
        if (!empty($abTest->winner_criteria_opens_count)) {
            $reachedMaxOpens = (int)$subject->opens_count >= (int)$abTest->winner_criteria_opens_count;

            // mark the date/time when reached, in case it hasn't been already
            if ($reachedMaxOpens && empty($abTest->winner_opens_count_reached_at)) {
                $abTestSaveAttributes['winner_opens_count_reached_at'] = (string)date('Y-m-d H:i:s');
            }
        }

        $reachedMaxDays = false;
        if (!empty($abTest->winner_criteria_days_count)) {
            $startDateTimestamp = (int)strtotime(
                !empty($abTest->winner_criteria_days_start_date) ?
                    (string)$abTest->winner_criteria_days_start_date :
                    (string)$this->started_at
            );
            $daysCountTimestamp = (int)strtotime(
                sprintf('+%d days', $abTest->winner_criteria_days_count),
                $startDateTimestamp
            );
            $reachedMaxDays = $daysCountTimestamp < time();

            // mark the date/time when reached, in case it hasn't been already
            if ($reachedMaxDays && empty($abTest->winner_days_count_reached_at)) {
                $abTestSaveAttributes['winner_days_count_reached_at'] = (string)date('Y-m-d H:i:s');
            }
        }

        // save any pending attributes
        if (!empty($abTestSaveAttributes)) {
            $abTest->saveAttributes($abTestSaveAttributes);
        }

        $reachedLimit = false;
        if ($abTest->winner_criteria_operator === CampaignAbtest::OPERATOR_OR) {
            $reachedLimit = $reachedMaxDays || $reachedMaxOpens;
        } elseif ($abTest->winner_criteria_operator === CampaignAbtest::OPERATOR_AND) {
            $reachedLimit = $reachedMaxDays && $reachedMaxOpens;
        }

        if (!$reachedLimit) {
            return false;
        }

        $opensCountReachedAt = !empty($abTest->winner_opens_count_reached_at)
            ? (int)strtotime((string)$abTest->winner_opens_count_reached_at)
            : 0;

        $daysCountReachedAt  = !empty($abTest->winner_days_count_reached_at)
            ? (int)strtotime((string)$abTest->winner_days_count_reached_at)
            : 0;

        $abTestSaveAttributes = [
            'status'            => CampaignAbtest::STATUS_COMPLETE,
            'completed_at'      => (string)date('Y-m-d H:i:s'),
        ];

        if (empty($daysCountReachedAt)) {
            // if the days haven't been reached yet, it's clear the winner was decided by opens.
            // this should happen when we use the OR operator
            $abTestSaveAttributes['winner_decided_by_opens_count'] = CampaignAbtest::TEXT_YES;
        } elseif (empty($opensCountReachedAt)) {
            // if the opens haven't been reached yet, it's clear the winner was decided by the days passing by.
            // this should happen when we use the OR operator
            $abTestSaveAttributes['winner_decided_by_days_count'] = CampaignAbtest::TEXT_YES;
        } else {
            // in this case, both limits have been reached, we need to select the most recent one.
            // this should happen when we use the AND operator

            if ($daysCountReachedAt < $opensCountReachedAt) {
                // the number of days has been reached before the opens were reached, so the opens decided the winner because we had to wait for them
                $abTestSaveAttributes['winner_decided_by_opens_count'] = CampaignAbtest::TEXT_YES;
            } elseif ($opensCountReachedAt < $daysCountReachedAt) {
                // the number of opens has been reached before the days were reached, so the days decided the winner because we had to wait for them
                $abTestSaveAttributes['winner_decided_by_days_count'] = CampaignAbtest::TEXT_YES;
            } else {
                // this should rarely happen, when both the opens and days are reached in the same time.
                $abTestSaveAttributes['winner_decided_by_opens_count'] = CampaignAbtest::TEXT_YES;
                $abTestSaveAttributes['winner_decided_by_days_count']  = CampaignAbtest::TEXT_YES;
            }
        }

        $abTest->saveAttributes($abTestSaveAttributes);

        // if $reachedMaxOpens is true, then it clearly means this subject has most opens.
        // however, if $reachedMaxDays is true, does not necessary means this particular subject won,
        // so we need to find the one with most opens, for this test, in this period.
        if ($reachedMaxDays && !$reachedMaxOpens) {
            $criteria = new CDbCriteria();
            $criteria->compare('test_id', (int)$subject->test_id);
            $criteria->compare('status', CampaignAbtestSubject::STATUS_ACTIVE);
            $criteria->order = 'opens_count DESC';
            $subject = CampaignAbtestSubject::model()->find($criteria);
        }

        $subject->saveAttributes([
            'is_winner' => CampaignAbtestSubject::TEXT_YES,
        ]);
        $this->saveAttributes([
            'subject'           => (string)$subject->subject,
            'subject_encoded'   => base64_encode((string)$subject->subject),
        ]);

        return true;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        if (empty($this->send_at)) {
            $this->send_at = date('Y-m-d H:i:s');
        }
        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        if ((string)$this->send_at === '0000-00-00 00:00:00') {
            $this->send_at = '';
        }

        if (empty($this->send_at)) {
            $this->send_at = date('Y-m-d H:i:s');
        }

        // since 1.3.9.3
        if (
            !empty($this->subject_encoded) &&
            ($subject = base64_decode($this->subject_encoded, true)) !== false &&
            $subject
        ) {
            $this->subject = $subject;
        }

        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ((string)$this->getScenario() === 'step-setup') {
            $tags = $this->getSubjectToNameAvailableTags();
            $hasErrors = false;
            $attributes = ['subject', 'to_name'];

            foreach ($attributes as $attribute) {
                $content = html_decode((string)($this->$attribute ?? ''));
                $missingTags = [];
                foreach ($tags as $tag) {
                    if (!isset($tag['tag']) || !isset($tag['required']) || !$tag['required']) {
                        continue;
                    }
                    if (!isset($tag['pattern']) && strpos($content, $tag['tag']) === false) {
                        $missingTags[] = $tag['tag'];
                    } elseif (isset($tag['pattern']) && !preg_match($tag['pattern'], $content)) {
                        $missingTags[] = $tag['tag'];
                    }
                }
                if (!empty($missingTags)) {
                    $missingTags = array_unique($missingTags);
                    $this->addError(
                        $attribute,
                        t(
                            'campaigns',
                            'The following tags are required but were not found in your content: {tags}',
                            ['{tags}' => implode(', ', $missingTags)]
                        )
                    );
                    $hasErrors = true;
                }
            }

            if ($hasErrors) {
                return false;
            }
        }

        return parent::beforeValidate();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function beforeSave()
    {
        if (empty($this->campaign_uid)) {
            $this->campaign_uid = $this->generateUid();
        }

        if ($this->getStatusIs(self::STATUS_PROCESSING) && !$this->getStartedAt()) {
            $this->started_at = MW_DATETIME_NOW;
        } elseif ($this->getStatusIs(self::STATUS_SENT)) {
            $this->finished_at = MW_DATETIME_NOW;
        } elseif ($this->getStatusIs(self::STATUS_DRAFT)) {
            $this->started_at  = null;
            $this->finished_at = null;
        }

        // since 1.3.9.3
        $this->subject_encoded = base64_encode((string)$this->subject);
        $this->subject         = StringHelper::remove4BytesChars((string)$this->subject);

        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        // since 1.3.5
        if (!$this->getIsPendingDelete()) {
            $this->name  .= '(' . t('app', 'Deleted') . ')';
            $this->status = self::STATUS_PENDING_DELETE;
            $this->save(false);
            return false;
        }

        // only drafts are allowed to be deleted
        if (!$this->getRemovable()) {
            return false;
        }

        return parent::beforeDelete();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        // clean campaign files, if any.
        $storagePath   = (string)Yii::getPathOfAlias('root.frontend.assets.gallery');
        $campaignFiles = $storagePath . '/cmp' . $this->campaign_uid;
        if (file_exists($campaignFiles) && is_dir($campaignFiles)) {
            FileSystemHelper::deleteDirectoryContents($campaignFiles, true, 1);
        }

        // attachments
        $attachmentsPath = (string)Yii::getPathOfAlias('root.frontend.assets.files.campaign-attachments.' . $this->campaign_uid);
        if (file_exists($attachmentsPath) && is_dir($attachmentsPath)) {
            FileSystemHelper::deleteDirectoryContents($attachmentsPath, true, 1);
        }

        parent::afterDelete();
    }

    /**
     * @return string
     * @throws CDbException
     */
    protected function _getStatusWithStats(): string
    {
        static $_status = [];
        if (!$this->getIsNewRecord() && isset($_status[$this->campaign_id])) {
            return $_status[$this->campaign_id];
        }

        if ($this->getIsNewRecord() || in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_SENDING])) {
            return $_status[$this->campaign_id] = $this->getStatusName();
        }

        // added in 1.3.4.7 to avoid confusion
        if ($this->getStatusIs(self::STATUS_SENT)) {
            return $_status[$this->campaign_id] = sprintf('%s (%d%s)', $this->getStatusName(), 100, '%');
        }

        $stats = $this->getStats();

        $percent = 0;
        if ($stats->getProcessedCount() > 0 && $stats->getSubscribersCount() > 0) {
            $percent = ((int)$stats->getProcessedCount() / (int)$stats->getSubscribersCount()) * 100;
        }
        if ($percent > 100) {
            $percent = 100;
        }
        $percent = formatter()->formatNumber($percent);

        return $_status[$this->campaign_id] = sprintf('%s (%d%s)', $this->getStatusName(), $percent, '%');
    }

    /**
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return int
     * @throws CDbException
     */
    protected function countSubscribersByListSegment(?CDbCriteria $mergeCriteria = null): int
    {
        if (!($criteria = $this->createCountSubscribersCriteria($mergeCriteria))) {
            return 0;
        }
        return (int)$this->segment->countSubscribers($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return array
     * @throws CDbException
     */
    protected function findSubscribersByListSegment(
        int $offset = 0,
        int $limit = 100,
        CDbCriteria $mergeCriteria = null
    ): array {
        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);

        if ($this->getIsAutoresponder() && !$this->addAutoresponderCriteria($criteria)) {
            return [];
        }

        if ($this->getIsRegular() && !$this->addRegularCriteria($criteria)) {
            return [];
        }

        if ($mergeCriteria) {
            $criteria->mergeWith($mergeCriteria);
        }

        // since 1.6.4
        /** @var CDbCriteria $criteria */
        $criteria = hooks()->applyFilters('campaign_model_find_subscribers_criteria', $criteria, $this);

        // 1.9.13
        // ListSegment::findSubscribers will also add the ordering
        // Ordering is important when fetching data from a replicated database which otherwise will return a
        // unordered result set which might cause unexpected behavior when using limit and offset.
        return (array)$this->segment->findSubscribers($offset, $limit, $criteria);
    }

    /**
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return int
     */
    protected function countSubscribersByList(?CDbCriteria $mergeCriteria = null): int
    {
        if (!($criteria = $this->createCountSubscribersCriteria($mergeCriteria))) {
            return 0;
        }
        return (int)ListSubscriber::model()->count($criteria);
    }

    /**
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return CDbCriteria|null
     */
    protected function createCountSubscribersCriteria(?CDbCriteria $mergeCriteria = null): ?CDbCriteria
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);

        if ($this->getIsAutoresponder() && !$this->addAutoresponderCriteria($criteria)) {
            return null;
        }

        if ($this->getIsRegular() && !$this->addRegularCriteria($criteria)) {
            return null;
        }

        if ($mergeCriteria) {
            $criteria->mergeWith($mergeCriteria);
        }

        // since 1.6.4
        /** @var CDbCriteria $criteria */
        $criteria = hooks()->applyFilters('campaign_model_count_subscribers_criteria', $criteria, $this);

        return $criteria;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param CDbCriteria|null $mergeCriteria
     *
     * @return array
     */
    protected function findSubscribersByList(int $offset = 0, int $limit = 100, CDbCriteria $mergeCriteria = null): array
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);
        $criteria->offset = $offset;
        $criteria->limit  = $limit;

        // 1.9.13
        // Ordering is important when fetching data from a replicated database which otherwise will return a
        // unordered result set which might cause unexpected behavior when using limit and offset.
        $criteria->order = 't.subscriber_id ASC';

        if ($this->getIsAutoresponder() && !$this->addAutoresponderCriteria($criteria)) {
            return [];
        }

        if ($this->getIsRegular() && !$this->addRegularCriteria($criteria)) {
            return [];
        }

        if ($mergeCriteria) {
            $criteria->mergeWith($mergeCriteria);
        }

        // since 1.6.4
        $criteria = hooks()->applyFilters('campaign_model_find_subscribers_criteria', $criteria, $this);

        return (array)ListSubscriber::model()->findAll($criteria);
    }

    /**
     * @param CDbCriteria $criteria
     * @return bool
     */
    protected function addRegularCriteria(CDbCriteria $criteria): bool
    {
        $criteria->with = is_array($criteria->with) ? $criteria->with : [];

        $filterOpenUnopenModels = CampaignFilterOpenUnopen::model()->findAllByAttributes([
            'campaign_id' => $this->campaign_id,
        ]);

        if (empty($filterOpenUnopenModels)) {
            return true;
        }

        $action = $filterOpenUnopenModels[0]['action'];
        $ids    = [];
        foreach ($filterOpenUnopenModels as $model) {
            $ids[] = (int)$model->previous_campaign_id;
        }
        $ids = array_filter(array_unique(array_map('intval', $ids)));

        $on = [];
        foreach ($ids as $id) {
            $on[] = 'trackOpens.campaign_id = ' . $id;
        }
        $on = implode(' OR ', $on);

        if ((string)$action === CampaignFilterOpenUnopen::ACTION_OPEN) {
            $criteria->with['trackOpens'] = [
                'select'    => false,
                'together'  => true,
                'joinType'  => 'INNER JOIN',
                'on'        => $on,
            ];
            return true;
        }

        if ((string)$action === CampaignFilterOpenUnopen::ACTION_UNOPEN) {
            $criteria->with['trackOpens'] = [
                'select'    => false,
                'together'  => true,
                'joinType'  => 'LEFT OUTER JOIN',
                'on'        => $on,
                'condition' => 'trackOpens.subscriber_id IS NULL',
            ];
            return true;
        }

        return true;
    }

    /**
     * @param CDbCriteria $criteria
     * @return bool
     *
     * @see https://github.com/onetwist-software/mailwizz/issues/470 for minTimeHour and minTimeMinute condition
     */
    protected function addAutoresponderCriteria(CDbCriteria $criteria): bool
    {
        $criteria->with = is_array($criteria->with) ? $criteria->with : [];

        // since 1.6.8
        $filterOpenUnopenModels = CampaignFilterOpenUnopen::model()->findAllByAttributes([
            'campaign_id' => $this->campaign_id,
        ]);

        if (!empty($filterOpenUnopenModels)) {
            $action = $filterOpenUnopenModels[0]['action'];
            $ids    = [];
            foreach ($filterOpenUnopenModels as $model) {
                $ids[] = (int)$model->previous_campaign_id;
            }
            $ids = array_filter(array_unique(array_map('intval', $ids)));

            $on = [];
            foreach ($ids as $id) {
                $on[] = 'trackOpens.campaign_id = ' . $id;
            }
            $on = implode(' OR ', $on);

            if ((string)$action === CampaignFilterOpenUnopen::ACTION_OPEN) {
                $criteria->with['trackOpens'] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'on'        => $on,
                ];
            }

            if ((string)$action === CampaignFilterOpenUnopen::ACTION_UNOPEN) {
                $criteria->with['trackOpens'] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'LEFT OUTER JOIN',
                    'on'        => $on,
                    'condition' => 'trackOpens.subscriber_id IS NULL',
                ];
            }
        }
        //

        if ((string)$this->option->autoresponder_include_imported === CampaignOption::TEXT_NO) {
            $criteria->addCondition('t.source != :src');
            $criteria->params[':src'] = ListSubscriber::SOURCE_IMPORT;
        }

        $minTimeHour   = !empty($this->option->autoresponder_time_min_hour) ? $this->option->autoresponder_time_min_hour : null;
        $minTimeMinute = !empty($this->option->autoresponder_time_min_minute) ? $this->option->autoresponder_time_min_minute : null;
        $timeValue     = (int)$this->option->autoresponder_time_value;
        $timeUnit      = strtoupper((string)$this->option->autoresponder_time_unit);

        // 1.7.4
        // we still need to load the subscribers here when queue tables in effect
        // we will set the right time later
        if (app_param('send.campaigns.command.useTempQueueTables', false)) {
            $minTimeHour = $minTimeMinute = null;
        }

        if ((string)$this->option->autoresponder_event === CampaignOption::AUTORESPONDER_EVENT_AFTER_SUBSCRIBE) {

            // since 1.4.2
            if ($this->option->autoresponder_include_current != CampaignOption::TEXT_YES) {
                $criteria->addCondition('t.date_added >= :cdate');
                $criteria->params[':cdate'] = $this->send_at;
            }

            $condition = sprintf('DATE_ADD(t.date_added, INTERVAL %d %s) <= NOW()', $timeValue, $timeUnit);

            // 1.4.3
            if (!empty($minTimeHour) && !empty($minTimeMinute)) {
                $condition .= sprintf(' AND 
            	    DATE_FORMAT(NOW(), \'%%Y-%%m-%%d %%H:%%i:%%s\') >= DATE_FORMAT(NOW(), \'%%Y-%%m-%%d %1$s:%2$s:00\')
            	', $minTimeHour, $minTimeMinute);
            }

            $criteria->addCondition($condition);
        } elseif ((string)$this->option->autoresponder_event === CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN) {
            if (empty($this->option->autoresponder_open_campaign_id)) {
                return false;
            }

            if (!is_array($criteria->with)) {
                $criteria->with = [];
            }

            $criteria->with['trackOpens'] = [
                'select'    => false,
                'joinType'  => 'INNER JOIN',
                'together'  => true,
                'on'        => 'trackOpens.campaign_id = :tocid',
                'condition' => 'trackOpens.id = (SELECT id FROM {{campaign_track_open}} WHERE campaign_id = :tocid AND subscriber_id = t.subscriber_id LIMIT 1)',
                'params'    => [':tocid' => $this->option->autoresponder_open_campaign_id],
            ];

            $condition = sprintf('DATE_ADD(trackOpens.date_added, INTERVAL %d %s) <= NOW()', $timeValue, $timeUnit);

            // 1.4.3
            if (!empty($minTimeHour) && !empty($minTimeMinute)) {
                $condition .= sprintf(
                    " AND 
                    DATE_FORMAT(DATE_ADD(trackOpens.date_added, INTERVAL %d %s), '%%Y-%%m-%%d %s:%s:00') <= NOW()",
                    $timeValue,
                    $timeUnit,
                    $minTimeHour,
                    $minTimeMinute
                );
            }

            $criteria->addCondition($condition);
        } elseif ((string)$this->option->autoresponder_event === CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT) {
            if (empty($this->option->autoresponder_sent_campaign_id)) {
                return false;
            }

            if (!is_array($criteria->with)) {
                $criteria->with = [];
            }

            $criteria->with['deliveryLogsSent'] = [
                'select'    => false,
                'joinType'  => 'INNER JOIN',
                'together'  => true,
                'on'        => 'deliveryLogsSent.campaign_id = :dlcid AND deliveryLogsSent.subscriber_id = t.subscriber_id',
                'params'    => [':dlcid' => $this->option->autoresponder_sent_campaign_id],
            ];

            $condition = sprintf('DATE_ADD(deliveryLogsSent.date_added, INTERVAL %d %s) <= NOW()', $timeValue, $timeUnit);

            // 1.4.3
            if (!empty($minTimeHour) && !empty($minTimeMinute)) {
                $condition .= sprintf(
                    " AND 
                    DATE_FORMAT(DATE_ADD(deliveryLogsSent.date_added, INTERVAL %d %s), '%%Y-%%m-%%d %s:%s:00') <= NOW()",
                    $timeValue,
                    $timeUnit,
                    $minTimeHour,
                    $minTimeMinute
                );
            }

            $criteria->addCondition($condition);
        } else {
            return false;
        }

        return true;
    }
}
