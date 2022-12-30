<?php declare(strict_types=1);

use Phemail\MessageParser;

if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBoxMonitor
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

/**
 * This is the model class for table "email_box_monitor".
 *
 * The followings are the available columns in table 'email_box_monitor':
 * @property integer $server_id
 * @property integer $customer_id
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
 * @property string $meta_data
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class EmailBoxMonitor extends BounceServer
{
    /**
     * Conditions list
     */
    const CONDITION_CONTAINS = 'contains';

    /**
     * Actions list
     */
    const ACTION_UNSUBSCRIBE           = 'unsubscribe';
    const ACTION_BLACKLIST             = 'blacklist';
    const ACTION_UNCONFIRM             = 'unconfirm';
    const ACTION_DELETE                = 'delete';
    const ACTION_MOVE_TO_LIST          = 'move to list';
    const ACTION_COPY_TO_LIST          = 'copy to list';
    const ACTION_STOP_CAMPAIGN_GROUP   = 'stop campaign group';

    /**
     * Identify list
     */
    const IDENTIFY_SUBSCRIBERS_BY_EMAIL     = 'by email address';
    const IDENTIFY_SUBSCRIBERS_BY_UID       = 'by subscriber uid';
    const IDENTIFY_SUBSCRIBERS_UID_OR_EMAIL = 'by subscriber uid or email address';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{email_box_monitor}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['hostname, username, password, port, service, protocol, validate_ssl', 'required'],

            ['hostname, username, password', 'length', 'min' => 3, 'max'=>150],
            ['email', 'email', 'validateIDN' => true],
            ['port', 'numerical', 'integerOnly'=>true],
            ['port', 'length', 'min'=> 2, 'max' => 5],
            ['protocol', 'in', 'range' => array_keys($this->getProtocolsArray())],
            ['customer_id', 'exist', 'className' => Customer::class, 'attributeName' => 'customer_id', 'allowEmpty' => true],
            ['locked', 'in', 'range' => array_keys($this->getYesNoOptions())],

            ['disable_authenticator, search_charset', 'length', 'max' => 50],
            ['delete_all_messages', 'in', 'range' => array_keys($this->getYesNoOptions())],

            ['conditions, identifySubscribersBy', 'required'],
            ['conditions', '_validateConditions'],
            ['identifySubscribersBy', 'in', 'range' => array_keys($this->getIdentifySubscribersByList())],

            ['hostname, username, service, port, protocol, status, customer_id', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'identifySubscribersBy' => t('servers', 'How to identify subscribers'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $labels = [
            'identifySubscribersBy' => t('servers', 'Subscriber UID means we will only identify subscribers who reply to a certain email campaign thus subscribers in a particular list. Email address means we will match subscribers with the given email address in all lists.'),
        ];

        return CMap::mergeArray($labels, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EmailBoxMonitor the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var EmailBoxMonitor $parent */
        $parent = parent::model($className);

        return $parent;
    }

    /**
     * @return array
     */
    public function getConditionsList(): array
    {
        return [
            self::CONDITION_CONTAINS => t('servers', ucfirst(self::CONDITION_CONTAINS)),
        ];
    }

    /**
     * @return array
     */
    public function getActionsList(): array
    {
        $options = [
            self::ACTION_UNSUBSCRIBE => t('servers', ucfirst(self::ACTION_UNSUBSCRIBE)),
            self::ACTION_BLACKLIST   => t('servers', ucfirst(self::ACTION_BLACKLIST)),
            self::ACTION_UNCONFIRM   => t('servers', ucfirst(self::ACTION_UNCONFIRM)),
            self::ACTION_DELETE      => t('servers', ucfirst(self::ACTION_DELETE)),
        ];

        if (is_cli() || apps()->isAppName('customer')) {
            $options = CMap::mergeArray($options, [
                self::ACTION_MOVE_TO_LIST          => t('servers', ucfirst(self::ACTION_MOVE_TO_LIST)),
                self::ACTION_COPY_TO_LIST          => t('servers', ucfirst(self::ACTION_COPY_TO_LIST)),
                self::ACTION_STOP_CAMPAIGN_GROUP   => t('servers', ucfirst(self::ACTION_STOP_CAMPAIGN_GROUP)),
            ]);
        }

        return $options;
    }

    /**
     * @param array $value
     *
     * @throws CException
     */
    public function setConditions(array $value = []): void
    {
        $this->modelMetaData->getModelMetaData()->add('conditions', (array)$this->filterConditions($value));
    }

    /**
     * @return array
     * @throws CException
     */
    public function getConditions(): array
    {
        $conditions = (array)$this->modelMetaData->getModelMetaData()->itemAt('conditions');
        return (array)$this->filterConditions($conditions);
    }

    /**
     * @param string $attribute
     * @param array $params
     * @throws CException
     */
    public function _validateConditions(string $attribute, array $params = []): void
    {
        $value = $this->getConditions();
        if (empty($value)) {
            $this->addError($attribute, t('servers', 'Please enter at least one valid condition'));
            return;
        }
    }

    /**
     * @return array
     */
    public function getIdentifySubscribersByList(): array
    {
        return [
            self::IDENTIFY_SUBSCRIBERS_BY_EMAIL     => t('servers', ucfirst(self::IDENTIFY_SUBSCRIBERS_BY_EMAIL)),
            self::IDENTIFY_SUBSCRIBERS_BY_UID       => t('servers', ucfirst(self::IDENTIFY_SUBSCRIBERS_BY_UID)),
            self::IDENTIFY_SUBSCRIBERS_UID_OR_EMAIL => t('servers', ucfirst(self::IDENTIFY_SUBSCRIBERS_UID_OR_EMAIL)),
        ];
    }

    /**
     * @param string $value
     * @throws CException
     */
    public function setIdentifySubscribersBy(string $value): void
    {
        if (empty($value) || !is_string($value) || !in_array($value, array_keys($this->getIdentifySubscribersByList()))) {
            $value = self::IDENTIFY_SUBSCRIBERS_BY_EMAIL;
        }
        $this->modelMetaData->getModelMetaData()->add('identify_subscribers_by', (string)$value);
    }

    /**
     * @return string
     * @throws CException
     */
    public function getIdentifySubscribersBy(): string
    {
        $value = (string)$this->modelMetaData->getModelMetaData()->itemAt('identify_subscribers_by');
        if (empty($value) || !is_string($value) || !in_array($value, array_keys($this->getIdentifySubscribersByList()))) {
            $value = self::IDENTIFY_SUBSCRIBERS_BY_EMAIL;
        }
        return $value;
    }

    /**
     * @return array
     */
    public function getCustomerEmailListsAsOptions(): array
    {
        if (!apps()->isAppName('customer')) {
            return [];
        }

        return ListsCollection::findAll([
            'select'    => 'list_id, name',
            'condition' => 'customer_id = :cid',
            'params'    => [':cid' => (int)customer()->getId()],
        ])->mapWithKeys(function (Lists $list) {
            return [$list->list_id => $list->name];
        })->all();
    }

    /**
     * @return array
     */
    public function getCustomerCampaignGroupsAsOptions(): array
    {
        if (!apps()->isAppName('customer')) {
            return [];
        }

        $options = [];
        $models = CampaignGroup::model()->findAll([
            'select'    => 'group_id, name',
            'condition' => 'customer_id = :cid',
            'params'    => [':cid' => (int)customer()->getId()],
        ]);

        foreach ($models as $model) {
            $options[$model->group_id] = $model->name;
        }

        return $options;
    }

    /**
     * @return bool
     * @throws CException
     */
    public function getConditionsContainEmailList(): bool
    {
        $conditions = $this->modelMetaData->getModelMetaData()->itemAt('conditions');
        $conditions = is_array($conditions) ? $conditions : [];
        if (empty($conditions)) {
            return false;
        }

        $actions = [self::ACTION_COPY_TO_LIST, self::ACTION_MOVE_TO_LIST];
        foreach ($conditions as $condition) {
            if (empty($condition['action'])) {
                continue;
            }
            if (in_array($condition['action'], $actions) && !empty($condition['list_id'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function _processRemoteContents(array $params = []): bool
    {
        $mutexKey = sha1('imappop3box' . serialize($this->getAttributes(['hostname', 'username', 'password'])) . date('Ymd'));
        if (!mutex()->acquire($mutexKey, 5)) {
            return false;
        }

        try {
            if (!$this->getIsActive()) {
                throw new Exception('The server is inactive!', 1);
            }

            $conditions = $this->getConditions();
            if (empty($conditions)) {
                throw new Exception('There are no conditions defined!', 1);
            }

            // 1.4.4
            $logger = !empty($params['logger']) && is_callable($params['logger']) ? $params['logger'] : null;

            // put proper status
            $this->saveStatus(self::STATUS_CRON_RUNNING);

            // make sure the BounceHandler class is loaded
            Yii::import('common.vendors.BounceHandler.*');

            /** @var OptionCronProcessEmailBoxMonitors $optionCronProcessEmailBoxMonitors */
            $optionCronProcessEmailBoxMonitors = container()->get(OptionCronProcessEmailBoxMonitors::class);

            $processLimit    = $optionCronProcessEmailBoxMonitors->getEmailsAtOnce();
            $processDaysBack = $optionCronProcessEmailBoxMonitors->getDaysBack();

            // close the db connection because it will time out!
            db()->setActive(false);

            $connectionStringSearchReplaceParams = [];
            if (!empty($params['mailbox'])) {
                $connectionStringSearchReplaceParams['[MAILBOX]'] = $params['mailbox'];
            }
            $connectionString = $this->getConnectionString($connectionStringSearchReplaceParams);

            $bounceHandler = new BounceHandler($connectionString, $this->username, $this->password, [
                'deleteMessages'    => true,
                'deleteAllMessages' => $this->getDeleteAllMessages(),
                'processLimit'      => $processLimit,
                'searchCharset'     => $this->getSearchCharset(),
                'imapOpenParams'    => $this->getImapOpenParams(),
                'processDaysBack'   => $processDaysBack,
                'logger'            => $logger,
            ]);

            // 1.4.4
            if ($logger) {
                $mailbox = $connectionStringSearchReplaceParams['[MAILBOX]'] ?? $this->mailBox;
                call_user_func($logger, sprintf('Searching for results in the "%s" mailbox...', $mailbox));
            }

            // fetch the results
            $results = $bounceHandler->getSearchResults();

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
                if ($logger) {
                    call_user_func($logger, sprintf('Processing message id: %s!', $result));
                }

                // load the full message
                $message = (string)imap_fetchbody($bounceHandler->getConnection(), $result, '');
                if (empty($message)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('Cannot fetch content for message id: %s!', $result));
                    }
                    if ($this->getDeleteAllMessages()) {
                        imap_delete($bounceHandler->getConnection(), (string)$result);
                    }
                    continue;
                }

                // since 1.9.10
                $parser             = new MessageParser();
                $normalizedMessage  = (string)preg_replace("/(\r\n|\n\r|\r)/", "\n", trim((string)$message));
                $messageParts       = (array)preg_split("/(\n)/", $normalizedMessage);
                $parsedMessage      = $parser->parse($messageParts);

                /** @var \Phemail\Message\MessagePartInterface|null $plainText */
                $plainText = null;

                if ($parsedMessage->isMultiPart()) {
                    foreach ($parsedMessage->getParts() as $part) {
                        if ($part->isText()) {
                            $plainText = $part;
                            break;
                        }
                    }
                } elseif ($parsedMessage->isText()) {
                    $plainText = $parsedMessage;
                }

                // we did not find the plain text message, there is nothing else we can do because
                // there is no proper way to parse the html and get the right text to test against the conditions.
                if (empty($plainText)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('Cannot fetch the plain text version for message id: %s!', $result));
                    }
                    continue;
                }

                $messageLines = [];
                $lines        = array_filter(array_unique(array_map('trim', explode("\n", $plainText->getContents()))));
                foreach ($lines as $line) {
                    // did we reach the quoted message? If so, stop.
                    if (strpos(trim((string)$line), '>') === 0) {
                        break;
                    }
                    // is this the first line of the reply quote:
                    // "On Thu, Dec 2, 2021 at 11:43 AM Some Name <email@domain.com> wrote:"
                    if (preg_match('/<([^>]+)>/', $line, $matches)) {
                        if (FilterVarHelper::email($matches[1])) {
                            // indeed, this is the first line, so we just skip it
                            continue;
                        }
                    }
                    $messageLines[] = $line;
                }
                if (empty($messageLines)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('Cannot find message lines for the plain text version of message id: %s!', $result));
                    }
                    continue;
                }
                $messageContent = implode("\n", $messageLines);
                //

                $condition = [];
                foreach ($conditions as $_condition) {
                    $value        = (string)$_condition['value'];
                    $encodedValue = (string)quoted_printable_encode($value);
                    if (
                        $_condition['condition'] == self::CONDITION_CONTAINS &&
                        ($value === '*' || stripos($messageContent, $value) !== false || stripos($messageContent, $encodedValue) !== false)
                    ) {
                        $condition = $_condition;
                        break;
                    }
                }

                if (empty($condition)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('Cannot find conditions to apply for message id: %s!', $result));
                    }
                    if ($this->getDeleteAllMessages()) {
                        imap_delete($bounceHandler->getConnection(), (string)$result);
                    }
                    continue;
                }

                if ($logger) {
                    call_user_func($logger, sprintf('Following action will be taken against message %s: %s!', $result, $condition['action']));
                }

                // get the header info
                $headerInfo = imap_headerinfo($bounceHandler->getConnection(), $result);
                if (empty($headerInfo) || empty($headerInfo->from) || empty($headerInfo->from[0]->mailbox) || empty($headerInfo->from[0]->host)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('Cannot fetch header info for message id: %s!', $result));
                    }
                    if ($this->getDeleteAllMessages()) {
                        imap_delete($bounceHandler->getConnection(), (string)$result);
                    }
                    continue;
                }
                $fromAddress = $headerInfo->from[0]->mailbox . '@' . $headerInfo->from[0]->host;

                if ($logger) {
                    call_user_func($logger, sprintf('Message %s targets following email address: %s!', $result, $fromAddress));
                }

                $subscribers           = [];
                $identifySubscribersBy = $this->getIdentifySubscribersBy();

                /** @var Campaign|null $campaign */
                $campaign = null;

                /** @var ListSubscriber|null $subscriber */
                $subscriber = null;

                if ($identifySubscribersBy == self::IDENTIFY_SUBSCRIBERS_BY_UID || $identifySubscribersBy == self::IDENTIFY_SUBSCRIBERS_UID_OR_EMAIL) {

                    // We try to identify the campaign and subscriber based on our custom tracking links
                    if (preg_match('/\/([a-z0-9]{13})\/track\-opening\/([a-z0-9]{13})/ix', $message, $matches)) {
                        /** @var Campaign|null $campaign */
                        $campaign = Campaign::model()->findByAttributes(['campaign_uid' => $matches[1]]);

                        /** @var ListSubscriber|null $subscriber */
                        $subscriber = ListSubscriber::model()->findByAttributes(['subscriber_uid' => $matches[2]]);
                    }

                    // since 2.0.34: In-Reply-To
                    if (empty($campaign) && empty($subscriber) && preg_match_all('/(In\-Reply\-To): ([^\r\n]+)/sim', $message, $matches)) {
                        $messageIds = array_reverse(!empty($matches[2]) && is_array($matches[2]) ? $matches[2] : []);
                        foreach ($messageIds as $messageId) {
                            /** @var CampaignDeliveryLog|null $log */
                            $log = CampaignDeliveryLog::model()->findByEmailMessageId($messageId);
                            if (!empty($log)) {
                                $campaign   = $log->campaign;
                                $subscriber = $log->subscriber;
                                break;
                            }
                        }
                    }

                    // since 2.0.34: Message-Id
                    if (empty($campaign) && empty($subscriber) && preg_match_all('/(Message\-Id): ([^\r\n]+)/sim', $message, $matches)) {
                        $messageIds = array_reverse(!empty($matches[2]) && is_array($matches[2]) ? $matches[2] : []);
                        foreach ($messageIds as $messageId) {
                            /** @var CampaignDeliveryLog|null $log */
                            $log = CampaignDeliveryLog::model()->findByEmailMessageId($messageId);
                            if (!empty($log)) {
                                $campaign   = $log->campaign;
                                $subscriber = $log->subscriber;
                                break;
                            }
                        }
                    }
                }

                if ($identifySubscribersBy == self::IDENTIFY_SUBSCRIBERS_BY_UID) {
                    if (!empty($subscriber)) {
                        $subscribers[] = $subscriber;
                    }
                } elseif ($identifySubscribersBy == self::IDENTIFY_SUBSCRIBERS_BY_EMAIL) {
                    $criteria = new CDbCriteria();
                    $criteria->compare('t.email', $fromAddress);
                    $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);
                    if (!empty($this->customer_id) && !empty($this->customer)) {
                        $criteria->addInCondition('t.list_id', $this->customer->getAllListsIds());
                    }
                    $subscribers = ListSubscriber::model()->findAll($criteria);
                } elseif ($identifySubscribersBy == self::IDENTIFY_SUBSCRIBERS_UID_OR_EMAIL) {
                    if (!empty($subscriber)) {
                        $subscribers[] = $subscriber;
                    }

                    if (empty($subscribers)) {
                        $criteria = new CDbCriteria();
                        $criteria->compare('t.email', $fromAddress);
                        $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);
                        if (!empty($this->customer_id) && !empty($this->customer)) {
                            $criteria->addInCondition('t.list_id', $this->customer->getAllListsIds());
                        }
                        $subscribers = ListSubscriber::model()->findAll($criteria);
                    }
                }

                if (empty($subscribers)) {
                    if ($logger) {
                        call_user_func($logger, sprintf('No subscriber found for message id: %s!', $result));
                    }
                    if ($this->getDeleteAllMessages()) {
                        imap_delete($bounceHandler->getConnection(), (string)$result);
                    }
                    continue;
                }

                if ($logger) {
                    call_user_func($logger, sprintf(
                        'Found %d email addresses for message %s which we will %s!',
                        (is_countable($subscribers) ? count($subscribers) : 0),
                        $result,
                        $condition['action']
                    ));
                }

                foreach ($subscribers as $subscriber) {
                    if ($condition['action'] == self::ACTION_UNSUBSCRIBE) {
                        $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);

                        if ($campaign) {
                            $count = CampaignTrackUnsubscribe::model()->countByAttributes([
                                'campaign_id'   => $campaign->campaign_id,
                                'subscriber_id' => $subscriber->subscriber_id,
                            ]);
                            if (empty($count)) {
                                $trackUnsubscribe                = new CampaignTrackUnsubscribe();
                                $trackUnsubscribe->campaign_id   = (int)$campaign->campaign_id;
                                $trackUnsubscribe->subscriber_id = (int)$subscriber->subscriber_id;
                                $trackUnsubscribe->note          = 'Unsubscribed via Email Box Monitor!';
                                $trackUnsubscribe->save(false);
                            }
                        }
                    } elseif ($condition['action'] == self::ACTION_UNCONFIRM) {
                        $subscriber->saveStatus(ListSubscriber::STATUS_UNCONFIRMED);
                    } elseif ($condition['action'] == self::ACTION_BLACKLIST) {
                        $subscriber->saveStatus(self::ACTION_BLACKLIST);
                    } elseif ($condition['action'] == self::ACTION_DELETE) {
                        $subscriber->delete();
                    } elseif ($condition['action'] == self::ACTION_COPY_TO_LIST && !empty($condition['list_id'])) {
                        $subscriber->copyToList((int)$condition['list_id']);
                    } elseif ($condition['action'] == self::ACTION_MOVE_TO_LIST && !empty($condition['list_id'])) {
                        $subscriber->moveToList((int)$condition['list_id']);
                    } elseif ($condition['action'] == self::ACTION_STOP_CAMPAIGN_GROUP && !empty($condition['campaign_group_id'])) {
                        try {
                            $block = new CampaignGroupBlockSubscriber();
                            $block->group_id        = (int)$condition['campaign_group_id'];
                            $block->subscriber_id   = (int)$subscriber->subscriber_id;
                            $block->save(false);
                        } catch (Exception $e) {
                        }
                    }
                }

                if ($bounceHandler->deleteMessages) {
                    imap_delete($bounceHandler->getConnection(), (string)$result);
                }
            }

            $bounceHandler->closeConnection();

            // mark the server as active once again
            $this->saveStatus(self::STATUS_ACTIVE);
        } catch (Exception $e) {
            if ($e->getCode() == 0) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        mutex()->release($mutexKey);

        return true;
    }

    /**
     * @param mixed $value
     * @return array
     */
    protected function filterConditions($value): array
    {
        if (empty($value) || !is_array($value)) {
            return [];
        }

        // reset the indexes because we're sending high indexes from js
        $value = array_values($value);

        // 1.8.8
        $hashes = [];

        $hasMove = false;
        foreach ($value as $index => $val) {
            if (!is_array($val) || empty($val['condition']) || empty($val['value']) || empty($val['action'])) {
                unset($value[$index]);
                continue;
            }

            if (!is_string($val['condition']) || !in_array($val['condition'], array_keys($this->getConditionsList()))) {
                unset($value[$index]);
                continue;
            }

            if (!is_string($val['value']) || strlen($val['value']) > 500) {
                unset($value[$index]);
                continue;
            }

            if (!is_string($val['action']) || !in_array($val['action'], array_keys($this->getActionsList()))) {
                unset($value[$index]);
                continue;
            }

            // 1.8.8
            $hash = sha1((string)json_encode($val));
            if (isset($hashes[$hash])) {
                unset($value[$index]);
                continue;
            }
            $hashes[$hash] = true;
            //

            if (in_array($val['action'], [self::ACTION_MOVE_TO_LIST, self::ACTION_COPY_TO_LIST])) {
                $value[$index]['campaign_group_id'] = 0;

                if ($val['action'] == self::ACTION_MOVE_TO_LIST && $hasMove) {
                    unset($value[$index]);
                    continue;
                }

                if ($val['action'] == self::ACTION_MOVE_TO_LIST) {
                    $hasMove = true;
                }

                if (empty($val['list_id']) || !is_numeric($val['list_id']) || $val['list_id'] == 0) {
                    unset($value[$index]);
                    continue;
                }

                $attributes = [
                    'list_id' => (int)$val['list_id'],
                ];
                if (apps()->isAppName('customer')) {
                    $attributes['customer_id'] = (int)customer()->getId();
                }
                $list = Lists::model()->findByAttributes($attributes);

                if (empty($list)) {
                    unset($value[$index]);
                    continue;
                }
            } elseif ($val['action'] == self::ACTION_STOP_CAMPAIGN_GROUP) {
                $value[$index]['list_id'] = 0;

                if (empty($val['campaign_group_id']) || !is_numeric($val['campaign_group_id']) || $val['campaign_group_id'] == 0) {
                    unset($value[$index]);
                    continue;
                }

                $attributes = [
                    'group_id' => (int)$val['campaign_group_id'],
                ];
                if (apps()->isAppName('customer')) {
                    $attributes['customer_id'] = (int)customer()->getId();
                }
                $group = CampaignGroup::model()->findByAttributes($attributes);

                if (empty($group)) {
                    unset($value[$index]);
                    continue;
                }
            } else {
                $value[$index]['list_id']           = 0;
                $value[$index]['campaign_group_id'] = 0;
            }
        }


        return $value;
    }
}
