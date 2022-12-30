<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BounceServer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "bounce_server".
 *
 * The followings are the available columns in table 'bounce_server':
 * @property integer|null $server_id
 * @property integer|string $customer_id
 * @property string $name
 * @property string $hostname
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $service
 * @property integer $port
 * @property string $protocol
 * @property string $validate_ssl
 * @property string $locked
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 * @property string $search_charset
 * @property string $delete_all_messages
 *
 * The followings are the available model relations:
 * @property DeliveryServer[] $deliveryServers
 * @property Customer $customer
 */
class BounceServer extends ActiveRecord
{

    /**
     * Flag
     */
    const STATUS_CRON_RUNNING = 'cron-running';

    /**
     * Flag
     */
    const STATUS_HIDDEN = 'hidden';

    /**
     * Flag
     */
    const STATUS_DISABLED = 'disabled';
    /**
     * @var bool
     */
    public $settingsChanged = false;

    /**
     * @var string
     */
    public $mailBox = 'INBOX';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{bounce_server}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'passwordHandler' => [
                'class' => 'common.components.db.behaviors.RemoteServerPasswordHandlerBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['hostname, username, password, port, service, protocol, validate_ssl', 'required'],

            ['name, hostname, username', 'length', 'min' => 3, 'max'=>150],
            ['password', 'length', 'min' => 3, 'max' => 255],
            ['email', 'email', 'validateIDN' => true],
            ['port', 'numerical', 'integerOnly'=>true],
            ['port', 'length', 'min'=> 2, 'max' => 5],
            ['protocol', 'in', 'range' => array_keys($this->getProtocolsArray())],
            ['service', 'in', 'range' => array_keys($this->getServicesArray())],

            ['customer_id', 'exist', 'className' => Customer::class, 'attributeName' => 'customer_id', 'allowEmpty' => true],
            ['locked', 'in', 'range' => array_keys($this->getYesNoOptions())],

            // since 1.3.5.5
            ['disable_authenticator, search_charset', 'length', 'max' => 50],
            ['delete_all_messages', 'in', 'range' => array_keys($this->getYesNoOptions())],
            //

            ['hostname, username, service, port, protocol, status, customer_id', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'deliveryServers'   => [self::HAS_MANY, DeliveryServer::class, 'bounce_server_id'],
            'customer'          => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'server_id'     => $this->t('Server'),
            'customer_id'   => $this->t('Customer'),
            'name'          => $this->t('Name'),
            'hostname'      => $this->t('Hostname'),
            'username'      => $this->t('Username'),
            'password'      => $this->t('Password'),
            'email'         => $this->t('Email'),
            'service'       => $this->t('Service'),
            'port'          => $this->t('Port'),
            'protocol'      => $this->t('Protocol'),
            'validate_ssl'  => $this->t('Validate ssl'),
            'locked'        => $this->t('Locked'),

            // since 1.3.5.5
            'disable_authenticator' => $this->t('Disable authenticator'),
            'search_charset'        => $this->t('Search charset'),
            'delete_all_messages'   => $this->t('Delete all messages'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return CActiveDataProvider
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with = [
                    'customer' => [
                        'joinType'  => 'INNER JOIN',
                        'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :name',
                        'params'    => [
                            ':name'    => '%' . $customerId . '%',
                        ],
                    ],
                ];
            }
        }

        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.hostname', $this->hostname, true);
        $criteria->compare('t.username', $this->username, true);
        $criteria->compare('t.email', $this->email, true);
        $criteria->compare('t.service', $this->service);
        $criteria->compare('t.port', $this->port);
        $criteria->compare('t.protocol', $this->protocol);
        $criteria->compare('t.status', $this->status);

        $criteria->addNotInCondition('t.status', [self::STATUS_HIDDEN]);

        $criteria->order = 't.hostname ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.server_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return BounceServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var BounceServer $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'          => $this->t('The name of this server to make a distinction if having multiple servers with same hostname.'),
            'hostname'      => $this->t('The hostname of your IMAP/POP3 server.'),
            'username'      => $this->t('The username of your IMAP/POP3 server, usually something like you@domain.com.'),
            'password'      => $this->t('The password of your IMAP/POP3 server, used in combination with your username to authenticate your request.'),
            'email'         => $this->t('Only if your login username to this server is not an email address. If left empty, the username will be used.'),
            'service'       => $this->t('The type of your server.'),
            'port'          => $this->t('The port of your IMAP/POP3 server, usually for IMAP this is 143 and for POP3 it is 110. If you are using SSL, then the port for IMAP is 993 and for POP3 it is 995.'),
            'protocol'      => $this->t('The security protocol used to access this server. If unsure, select NOTLS.'),
            'validate_ssl'  => $this->t('When using SSL/TLS, whether to validate the certificate or not.'),
            'locked'        => $this->t('Whether this server is locked and assigned customer cannot change or delete it'),

            // since 1.3.5.5
            'disable_authenticator' => $this->t('If in order to establish the connection you need to disable an authenticator, you can type it here. I.E: GSSAPI.'),
            'search_charset'        => $this->t('Search charset, defaults to UTF-8 but might require to leave empty for some servers or explictly use US-ASCII.'),
            'delete_all_messages'   => $this->t('By default only messages related to the application are deleted. If this is enabled, all messages from the box will be deleted.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'servers';
    }

    /**
     * @return array
     */
    public function getServicesArray(): array
    {
        return [
            'imap' => 'IMAP',
            'pop3' => 'POP3',
        ];
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->getServicesArray()[$this->service] ?? '---';
    }

    /**
     * @return array
     */
    public function getProtocolsArray(): array
    {
        return [
            'tls'   => 'TLS',
            'ssl'   => 'SSL',
            'notls' => 'NOTLS',
        ];
    }

    /**
     * @return string
     */
    public function getProtocolName(): string
    {
        return $this->getProtocolsArray()[$this->protocol] ?? t('app', 'Default');
    }

    /**
     * @return array
     */
    public function getValidateSslOptions(): array
    {
        return [
            self::TEXT_NO   => t('app', 'No'),
            self::TEXT_YES  => t('app', 'Yes'),
        ];
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        if (!empty($this->name)) {
            return $this->name;
        }

        return sprintf('%s - %s(%s)', strtoupper((string)$this->service), $this->hostname, $this->username);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getConnectionString(array $params = []): string
    {
        $params = CMap::mergeArray([
            '[HOSTNAME]'        => $this->hostname,
            '[PORT]'            => $this->port,
            '[SERVICE]'         => $this->service,
            '[PROTOCOL]'        => $this->protocol,
            '[MAILBOX]'         => $this->mailBox,
            '[/VALIDATE_CERT]'  => '',
        ], $params);

        if (($this->protocol == 'ssl' || $this->protocol == 'tls') && $this->validate_ssl == self::TEXT_NO) {
            $params['[/VALIDATE_CERT]'] = '/novalidate-cert';
        }

        /** @var array $params */
        $params = (array)hooks()->applyFilters('servers_imap_connection_string_search_replace_params', $params, $this);

        $connectionString = '{[HOSTNAME]:[PORT]/[SERVICE]/[PROTOCOL][/VALIDATE_CERT]}[MAILBOX]';
        return (string)str_replace(array_keys($params), array_values($params), $connectionString);
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return !in_array($this->status, [self::STATUS_CRON_RUNNING]);
    }

    /**
     * @return bool
     */
    public function getCanBeUpdated(): bool
    {
        return !in_array($this->status, [self::STATUS_CRON_RUNNING, self::STATUS_HIDDEN]);
    }

    /**
     * @return bool
     */
    public function getIsLocked(): bool
    {
        return (string)$this->locked === self::TEXT_YES;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_ACTIVE         => ucfirst(t('app', self::STATUS_ACTIVE)),
            self::STATUS_CRON_RUNNING   => ucfirst(t('app', self::STATUS_CRON_RUNNING)),
            self::STATUS_INACTIVE       => ucfirst(t('app', self::STATUS_INACTIVE)),
            self::STATUS_DISABLED       => ucfirst(t('app', self::STATUS_DISABLED)),
        ];
    }

    /**
     * @return array
     */
    public function getImapOpenParams(): array
    {
        $params = [];
        if (!empty($this->disable_authenticator)) {
            $params['DISABLE_AUTHENTICATOR'] = $this->disable_authenticator;
        }
        return $params;
    }

    /**
     * @return string
     */
    public function getSearchCharset(): string
    {
        return strtoupper((string)$this->search_charset);
    }

    /**
     * @return bool
     */
    public function getDeleteAllMessages(): bool
    {
        return (string)$this->delete_all_messages === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    public function testConnection(): bool
    {
        $this->validate();
        if ($this->hasErrors()) {
            return false;
        }

        if (!CommonHelper::functionExists('imap_open')) {
            $this->addError('hostname', $this->t('The IMAP extension is missing from your PHP installation.'));
            return false;
        }

        set_error_handler(function () {
            return false;
        }, E_WARNING);
        imap_timeout(IMAP_OPENTIMEOUT, 5);

        /** @var false|resource $conn */
        $conn   = imap_open($this->getConnectionString(), $this->username, $this->password, 0, 1, $this->getImapOpenParams());

        restore_error_handler();

        $errors = imap_errors();
        $error  = null;

        if (!empty($errors) && is_array($errors)) {
            $errors = array_unique(array_values($errors));
            $error  = implode('<br />', $errors);

            // since 1.3.5.8
            if (stripos($error, 'insecure server advertised') !== false) {
                $error = null;
            }
        }

        if (empty($error) && empty($conn)) {
            $error = $this->t('Unknown error while opening the connection!');
        }

        // since 1.3.5.9
        if (!empty($error) && stripos($error, 'Mailbox is empty') !== false) {
            $error = null;
        }

        if (!empty($error)) {
            $this->addError('hostname', $error);
            return false;
        }

        if (!$conn) {
            $this->addError('hostname', $this->t('Unknown error while opening the connection!'));
            return false;
        }

        $results = imap_search($conn, 'NEW', SE_FREE, $this->getSearchCharset());
        $errors  = imap_errors();
        $error   = null;
        if (!empty($errors) && is_array($errors)) {
            $errors = array_unique(array_values($errors));
            $error = implode('<br />', $errors);
        }
        imap_close($conn);

        // since 1.3.5.7
        if (!empty($error) && stripos($error, 'Mailbox is empty') !== false) {
            $error = null;
        }

        if (!empty($error)) {
            $this->addError('hostname', $error);
            return false;
        }

        return true;
    }

    /**
     * @return BounceServer|null
     * @throws CException
     */
    public function copy(): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {
            return null;
        }

        $transaction = db()->beginTransaction();

        try {
            $server = clone $this;
            $server->setIsNewRecord(true);
            $server->server_id    = null;
            $server->status       = self::STATUS_DISABLED;
            $server->date_added   = MW_DATETIME_NOW;
            $server->last_updated = MW_DATETIME_NOW;

            if (!$server->save(false)) {
                throw new CException($server->shortErrors->getAllAsString());
            }

            $transaction->commit();
            $copied = $server;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        return $copied;
    }

    /**
     * @return bool
     */
    public function getIsDisabled(): bool
    {
        return $this->getStatusIs(self::STATUS_DISABLED);
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function enable(): bool
    {
        if (!$this->getIsDisabled()) {
            return false;
        }
        return $this->saveStatus(self::STATUS_ACTIVE);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disable()
    {
        if (!$this->getIsActive()) {
            return false;
        }
        return $this->saveStatus(self::STATUS_DISABLED);
    }

    /**
     * @param array $params
     * @return bool
     */
    public function processRemoteContents(array $params = []): bool
    {
        $mailBoxes = (array)app_param('servers.imap.search.mailboxes', []);
        if (!empty($params['mailbox'])) {
            $mailBoxes[] = $params['mailbox'];
        }
        $mailBoxes = array_filter(array_unique(array_map('strtoupper', $mailBoxes)));
        $mailBoxes = !empty($mailBoxes) ? $mailBoxes : [$this->mailBox];

        foreach ($mailBoxes as $mailBox) {
            $this->_processRemoteContents(CMap::mergeArray($params, ['mailbox' => $mailBox]));
        }
        return true;
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    public function isValidBounceHandlerResultCallback(array $result): bool
    {
        return $this->getDeliveryLogFromBounceHandlerResult($result) !== null;
    }

    /**
     * @param array $result
     *
     * @return CampaignDeliveryLog|null
     */
    public function getDeliveryLogFromBounceHandlerResult(array $result): ?CampaignDeliveryLog
    {
        if (!isset($result['originalEmailHeadersArray']) || !is_array($result['originalEmailHeadersArray'])) {
            return null;
        }

        $headers = array_reverse((array)$result['originalEmailHeadersArray']);
        foreach ($headers as $key => $value) {
            if (strtolower($key) !== 'message-id') {
                continue;
            }

            /** @var CampaignDeliveryLog|null $log */
            $log = CampaignDeliveryLog::model()->findByEmailMessageId($value);
            if (!empty($log)) {
                return $log;
            }
        }

        return null;
    }

    /**
     * @param array $attributes
     * @param Customer|null $customer
     * @return static
     * @throws CException
     */
    public static function createFromArray(array $attributes, ?Customer $customer = null): self
    {
        // @phpstan-ignore-next-line
        $model = new static();

        if ($model instanceof EmailBoxMonitor && !empty($attributes['conditions'])) {
            $attributes['conditions'] = (array)json_decode((string)$attributes['conditions'], true);
        }

        if ($model instanceof EmailBoxMonitor && !empty($attributes['identify_subscribers_by'])) {
            $model->setIdentifySubscribersBy($attributes['identify_subscribers_by']);
        }

        $model->attributes = $attributes;
        $model->status     = self::STATUS_ACTIVE;

        // If customer received here, this is run from the customer area, and we force the received customer_id
        if (!empty($customer)) {
            $model->customer_id = (int)$customer->customer_id;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('hostname', (string)$model->hostname);
        $criteria->compare('username', (string)$model->username);

        // If no customer_id meaning system server
        if (empty($model->customer_id)) {
            $criteria->addCondition('customer_id IS NULL');
        } else {
            $criteria->compare('customer_id', (int)$model->customer_id);
        }

        $modelExists = static::model()->count($criteria);

        if ($modelExists) {
            $model->addError('hostname', t('servers', 'Server configuration "{customer} - {hostname} - {username}" already exists', [
                '{customer}' => !empty($model->customer) ? $model->customer->getFullName() : t('app', 'System'),
                '{hostname}' => $model->hostname,
                '{username}' => $model->username,
            ]));

            return $model;
        }

        if (!$model->testConnection()) {
            return $model;
        }

        $model->save();

        return $model;
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        $this->settingsChanged = false;

        if (!$this->getIsNewRecord() && !is_cli()) {
            if (empty($this->customer_id)) {
                $this->locked = self::TEXT_NO;
            }

            if (get_class($this) == 'BounceServer') {
                $model = self::model()->findByPk((int)$this->server_id);
                $keys  = ['hostname', 'username', 'password', 'email', 'service', 'port', 'protocol', 'validate_ssl'];
                foreach ($keys as $key) {
                    if (!empty($this->$key) && $this->$key != $model->$key) {
                        $this->settingsChanged = true;
                        break;
                    }
                }

                if ($this->settingsChanged) {
                    if (!empty($this->deliveryServers)) {
                        $deliveryServers = $this->deliveryServers;
                        foreach ($deliveryServers as $server) {
                            $server->status = DeliveryServer::STATUS_INACTIVE;
                            $server->save(false);
                        }
                    }
                }
            }
        }

        parent::afterValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!$this->getCanBeDeleted()) {
            return false;
        }

        return parent::beforeDelete();
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function _processRemoteContents(array $params = []): bool
    {
        // 1.4.4
        $logger = !empty($params['logger']) && is_callable($params['logger']) ? $params['logger'] : null;

        // 1.8.8
        if ($logger) {
            call_user_func($logger, sprintf('Acquiring lock for server ID %d.', $this->server_id));
        }

        $mutexKey = sha1('imappop3box' . serialize($this->getAttributes(['hostname', 'username', 'password'])));
        if (!mutex()->acquire($mutexKey, 5)) {
            // 1.8.8
            if ($logger) {
                call_user_func($logger, sprintf('Seems that server ID %d is already locked and processing.', $this->server_id));
            }
            return false;
        }

        // 1.8.8
        if ($logger) {
            call_user_func($logger, sprintf('Lock for server ID %d has been acquired.', $this->server_id));
        }

        try {
            if (!$this->getIsActive()) {
                throw new Exception('The server is inactive!', 1);
            }

            // put proper status
            $this->saveStatus(self::STATUS_CRON_RUNNING);

            // make sure the BounceHandler class is loaded
            Yii::import('common.vendors.BounceHandler.*');

            /** @var OptionCronProcessFeedbackLoopServers $optionCronProcessFeedbackLoopServers */
            $optionCronProcessFeedbackLoopServers = container()->get(OptionCronProcessFeedbackLoopServers::class);

            /** @var OptionCronProcessBounceServers $optionCronProcessBounceServers */
            $optionCronProcessBounceServers = container()->get(OptionCronProcessBounceServers::class);

            if ($this instanceof FeedbackLoopServer) {
                $processLimit    = $optionCronProcessFeedbackLoopServers->getEmailsAtOnce();
                $processDaysBack = $optionCronProcessFeedbackLoopServers->getDaysBack();
            } else {
                $processLimit    = $optionCronProcessBounceServers->getEmailsAtOnce();
                $processDaysBack = $optionCronProcessBounceServers->getDaysBack();
            }

            // close the db connection because it will time out!
            db()->setActive(false);

            $connectionStringSearchReplaceParams = [];
            if (!empty($params['mailbox'])) {
                $connectionStringSearchReplaceParams['[MAILBOX]'] = $params['mailbox'];
            }
            $connectionString = $this->getConnectionString($connectionStringSearchReplaceParams);

            $bounceHandler = new BounceHandler($connectionString, $this->username, $this->password, [
                'deleteMessages'                => true,
                'deleteAllMessages'             => $this->getDeleteAllMessages(),
                'processLimit'                  => $processLimit,
                'searchCharset'                 => $this->getSearchCharset(),
                'imapOpenParams'                => $this->getImapOpenParams(),
                'processDaysBack'               => $processDaysBack,
                'processOnlyFeedbackReports'    => ($this instanceof FeedbackLoopServer),
                'isValidResultCallback'         => [$this, 'isValidBounceHandlerResultCallback'],
                'logger'                        => $logger,
            ]);

            // 1.4.4
            if ($logger) {
                $mailbox = $connectionStringSearchReplaceParams['[MAILBOX]'] ?? $this->mailBox;
                call_user_func($logger, sprintf('Searching for results in the "%s" mailbox...', $mailbox));
            }

            // fetch the results
            $results = $bounceHandler->getResults();

            // 1.4.4
            if ($logger) {
                call_user_func($logger, sprintf('Found %d results.', count($results)));
            }

            // re-open the db connection
            db()->setActive(true);

            // done
            if (empty($results)) {
                $this->saveStatus(self::STATUS_ACTIVE);
                throw new Exception('No results!', 1);
            }

            foreach ($results as $result) {
                $log = $this->getDeliveryLogFromBounceHandlerResult($result);
                if (!$log) {
                    continue;
                }

                $campaign   = $log->campaign;
                $subscriber = $log->subscriber;

                // 1.4.4
                if ($logger) {
                    call_user_func($logger, sprintf('Processing campaign uid: %s and subscriber uid %s.', $campaign->campaign_uid, $subscriber->subscriber_uid));
                }

                if ($campaign->getIsPendingDelete()) {
                    // 1.4.4
                    if ($logger) {
                        call_user_func($logger, sprintf('Campaign uid: %s was not found anymore.', $campaign->campaign_uid));
                    }
                    continue;
                }

                if (!$subscriber->getIsConfirmed()) {
                    // 1.4.4
                    if ($logger) {
                        call_user_func($logger, sprintf('Subscriber uid: %s is not confirmed anymore.', $subscriber->subscriber_uid));
                    }
                    continue;
                }

                if (in_array($result['bounceType'], [BounceHandler::BOUNCE_SOFT, BounceHandler::BOUNCE_HARD])) {
                    $count = CampaignBounceLog::model()->countByAttributes([
                        'campaign_id'   => $campaign->campaign_id,
                        'subscriber_id' => $subscriber->subscriber_id,
                    ]);

                    if (!empty($count)) {
                        continue;
                    }

                    $bounceLog = new CampaignBounceLog();
                    $bounceLog->campaign_id     = (int)$campaign->campaign_id;
                    $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
                    $bounceLog->message         = $result['diagnosticCode'];
                    $bounceLog->bounce_type     = $result['bounceType'] == BounceHandler::BOUNCE_HARD ? BounceHandler::BOUNCE_HARD : CampaignBounceLog::BOUNCE_SOFT;
                    $bounceLog->save();

                    // since 1.3.5.9
                    if ($bounceLog->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
                        $subscriber->addToBlacklist($bounceLog->message);
                    }

                    // 1.4.4
                    if ($logger) {
                        call_user_func($logger, sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $subscriber->subscriber_uid, (string)$bounceLog->bounce_type, (string)$bounceLog->message));
                    }
                } elseif ($result['bounceType'] == BounceHandler::FEEDBACK_LOOP_REPORT) {
                    /** @var OptionCronProcessFeedbackLoopServers $fbl */
                    $fbl = container()->get(OptionCronProcessFeedbackLoopServers::class);
                    $fbl->takeActionAgainstSubscriberWithCampaign($subscriber, $campaign);

                    // 1.4.4
                    if ($logger) {
                        call_user_func($logger, sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $subscriber->subscriber_uid, (string)$result['bounceType'], 'FBL complaint!'));
                    }
                } elseif ($result['bounceType'] == BounceHandler::BOUNCE_INTERNAL) {
                    $bounceLog = new CampaignBounceLog();
                    $bounceLog->campaign_id     = (int)$campaign->campaign_id;
                    $bounceLog->subscriber_id   = (int)$subscriber->subscriber_id;
                    $bounceLog->message         = !empty($result['diagnosticCode']) ? $result['diagnosticCode'] : 'Internal Bounce';
                    $bounceLog->bounce_type     = BounceHandler::BOUNCE_INTERNAL;
                    $bounceLog->save();

                    // 1.4.4
                    if ($logger) {
                        call_user_func($logger, sprintf('Subscriber uid: %s is %s bounced with the message: %s.', $subscriber->subscriber_uid, (string)$bounceLog->bounce_type, (string)$bounceLog->message));
                    }
                }
            }

            // mark the server as active once again
            $this->saveStatus(self::STATUS_ACTIVE);
        } catch (Exception $e) {
            if ($e->getCode() == 0) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
            if ($logger) {
                call_user_func($logger, $e->getMessage());
            }
        }

        // 1.8.8
        if ($logger) {
            call_user_func($logger, sprintf('Releasing lock for server ID %d.', $this->server_id));
        }

        mutex()->release($mutexKey);

        return true;
    }
}
