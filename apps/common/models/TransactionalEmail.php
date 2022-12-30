<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TransactionalEmail
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{transactional_email}}".
 *
 * The followings are the available columns in table '{{transactional_email}}':
 * @property integer $email_id
 * @property string $email_uid
 * @property integer $customer_id
 * @property string $to_email
 * @property string $to_name
 * @property string $from_email
 * @property string $from_name
 * @property string $reply_to_email
 * @property string $reply_to_name
 * @property string $subject
 * @property string $body
 * @property string $plain_text
 * @property integer $priority
 * @property integer $retries
 * @property integer $max_retries
 * @property string $send_at
 * @property string $fallback_system_servers
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property TransactionalEmailLog[] $logs
 * @property TransactionalEmailAttachment[] $attachments
 */
class TransactionalEmail extends ActiveRecord
{
    /**
     * Flag for sent emails
     */
    const STATUS_SENT    = 'sent';

    /**
     * Flag for unset emails
     */
    const STATUS_UNSENT  = 'unsent';

    /**
     * @var bool
     */
    public $sendDirectly = false;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{transactional_email}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['to_email, to_name, from_name, subject, body, send_at', 'required'],
            ['to_email, to_name, from_email, from_name, reply_to_email, reply_to_name', 'length', 'max' => 150],
            ['to_email, from_email, reply_to_email', 'email', 'validateIDN' => true],
            ['subject', 'length', 'max' => 255],
            ['send_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'],

            // The following rule is used by search().
            ['to_email, to_name, from_email, from_name, reply_to_email, reply_to_name, subject, status', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer'    => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'logs'        => [self::HAS_MANY, TransactionalEmailLog::class, 'email_id'],
            'attachments' => [self::HAS_MANY, TransactionalEmailAttachment::class, 'email_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'email_id'       => t('transactional_emails', 'Email'),
            'customer_id'    => t('transactional_emails', 'Customer'),
            'to_email'       => t('transactional_emails', 'To email'),
            'to_name'        => t('transactional_emails', 'To name'),
            'from_email'     => t('transactional_emails', 'From email'),
            'from_name'      => t('transactional_emails', 'From name'),
            'reply_to_email' => t('transactional_emails', 'Reply to email'),
            'reply_to_name'  => t('transactional_emails', 'Reply to name'),
            'subject'        => t('transactional_emails', 'Subject'),
            'body'           => t('transactional_emails', 'Body'),
            'plain_text'     => t('transactional_emails', 'Plain text'),
            'priority'       => t('transactional_emails', 'Priority'),
            'retries'        => t('transactional_emails', 'Retries'),
            'max_retries'    => t('transactional_emails', 'Max retries'),
            'send_at'        => t('transactional_emails', 'Send at'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @param bool $runValidation
     * @param mixed $attributes
     *
     * @return bool
     * @throws CException
     */
    public function save($runValidation = true, $attributes = null)
    {
        $saved = parent::save($runValidation, $attributes);
        if ($saved && $this->sendDirectly) {
            return $this->send();
        }
        return $saved;
    }

    /**
     * @return bool
     * @throws CException
     */
    public function send(): bool
    {
        // 1.6.9
        if (empty($this->email_id)) {
            return false;
        }

        // since 1.3.7.3
        hooks()->doAction('transactional_emails_before_send', new CAttributeCollection([
            'instance' => $this,
        ]));

        static $servers     = [];
        $this->sendDirectly = false;
        $serverParams       = [
            'customerCheckQuota' => false,
            'serverCheckQuota'   => false,
            'useFor'             => [DeliveryServer::USE_FOR_TRANSACTIONAL],
        ];

        $cid = (int)$this->customer_id;
        if (!array_key_exists($cid, $servers)) {
            $servers[$cid] = DeliveryServer::pickServer(0, $this, $serverParams);
        }

        // since 1.9.22
        if (!empty($cid) && empty($servers[$cid]) && $this->getFallbackSystemServers()) {
            $this->customer_id = 0;
            $servers[$cid] = DeliveryServer::pickServer(0, $this, $serverParams);
        }

        if (empty($servers[$cid])) {
            $this->incrementPriority();
            return false;
        }

        $server = $servers[$cid];
        if (!$server->canSendToDomainOf($this->to_email)) {
            $this->incrementPriority();
            return false;
        }

        $customer = (!empty($this->customer_id) && !empty($this->customer) ? $this->customer : null);
        $blParams = ['checkZone' => EmailBlacklist::CHECK_ZONE_TRANSACTIONAL_EMAILS];
        if (EmailBlacklist::isBlacklisted($this->to_email, null, $customer, $blParams)) {
            if (!$this->getIsNewRecord()) {
                try {
                    $this->delete();
                } catch (Exception $e) {
                }
            }
            return false;
        }

        if ($server->getIsOverQuota()) {
            $currentServerId = (int)$server->server_id;
            if (!($servers[$cid] = DeliveryServer::pickServer((int)$currentServerId, $this, $serverParams))) {
                unset($servers[$cid]);

                $this->incrementPriority();
                return false;
            }
            $server = $servers[$cid];
        }

        if (!empty($this->customer_id) && $this->customer->getIsOverQuota()) {
            $this->incrementPriority();
            return false;
        }

        $emailParams = [
            'fromName'      => $this->from_name,
            'to'            => [$this->to_email => $this->to_name],
            'subject'       => $this->subject,
            'body'          => $this->body,
            'plainText'     => $this->plain_text,
        ];

        if (!empty($this->from_email)) {
            $emailParams['from'] = [$this->from_email => $this->from_name];
        }

        if (!empty($this->reply_to_name) && !empty($this->reply_to_email)) {
            $emailParams['replyTo'] = [$this->reply_to_email => $this->reply_to_name];
        }

        $attachments = TransactionalEmailAttachment::model()->findAll([
            'select'    => 'file',
            'condition' => 'email_id = :cid',
            'params'    => [':cid' => $this->email_id],
        ]);

        if (!empty($attachments) && is_array($attachments)) {
            $emailParams['attachments'] = [];
            foreach ($attachments as $attachment) {
                $emailParams['attachments'][] = (string)Yii::getPathOfAlias('root') . $attachment->file;
            }
        }

        $sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_TRANSACTIONAL)->setDeliveryObject($this)->sendEmail($emailParams);
        if ($sent) {
            $this->saveStatus(TransactionalEmail::STATUS_SENT);
        } else {
            $this->incrementRetries();
        }

        $log = new TransactionalEmailLog();
        $log->email_id = (int)$this->email_id;
        $log->message  = (string)$server->getMailer()->getLog();
        $log->save(false);

        // since 1.3.7.3
        hooks()->doAction('transactional_emails_after_send', new CAttributeCollection([
            'instance' => $this,
            'log'      => $log,
            'sent'     => $sent,
        ]));

        return !empty($sent);
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

        $criteria->compare('t.to_email', $this->to_email, true);
        $criteria->compare('t.to_name', $this->to_name, true);
        $criteria->compare('t.from_email', $this->from_email, true);
        $criteria->compare('t.from_name', $this->from_name, true);
        $criteria->compare('t.reply_to_email', $this->reply_to_email, true);
        $criteria->compare('t.reply_to_name', $this->reply_to_name, true);
        $criteria->compare('t.subject', $this->subject, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.email_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.email_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TransactionalEmail the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TransactionalEmail $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $email_uid
     *
     * @return TransactionalEmail|null
     */
    public function findByUid(string $email_uid): ?self
    {
        return self::model()->findByAttributes([
            'email_uid' => $email_uid,
        ]);
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
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->email_uid;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getSendAt(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDateTime((string)$this->send_at);
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_SENT   => t('transactional_emails', ucfirst(self::STATUS_SENT)),
            self::STATUS_UNSENT => t('transactional_emails', ucfirst(self::STATUS_UNSENT)),
        ];
    }

    /**
     * @param int $by
     */
    public function incrementPriority(int $by = 1): void
    {
        if (empty($this->email_id)) {
            return;
        }

        $this->priority = (int)$this->priority + (int)$by;

        $attributes = [
            'priority' => $this->priority,
        ];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;

        db()->createCommand()->update($this->tableName(), $attributes, 'email_id = :sid', [':sid' => (int)$this->email_id]);
    }

    /**
     * @param int $by
     */
    public function incrementRetries(int $by = 1): void
    {
        if (empty($this->email_id)) {
            return;
        }

        $this->retries = (int)$this->retries + (int)$by;

        $attributes = [
            'retries' => $this->retries,
        ];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;

        db()->createCommand()->update($this->tableName(), $attributes, 'email_id = :sid', [':sid' => (int)$this->email_id]);
    }

    /**
     * @return bool
     */
    public function getFallbackSystemServers(): bool
    {
        return !empty($this->fallback_system_servers) && $this->fallback_system_servers === self::TEXT_YES;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        if ((string)$this->send_at === '0000-00-00 00:00:00') {
            $this->send_at = '';
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
        parent::afterFind();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        // clean attachments files, if any.
        $attachmentsPath = (string)Yii::getPathOfAlias('root.frontend.assets.files.transactional-email-attachments.' . $this->email_uid);
        if (file_exists($attachmentsPath) && is_dir($attachmentsPath)) {
            FileSystemHelper::deleteDirectoryContents($attachmentsPath, true, 1);
        }

        parent::afterDelete();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (empty($this->send_at)) {
            $this->send_at = date('Y-m-d H:i:s');
        }
        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->plain_text) && !empty($this->body)) {
            $this->plain_text = CampaignHelper::htmlToText($this->body);
        }
        if (empty($this->email_uid)) {
            $this->email_uid = $this->generateUid();
        }
        $customer = !empty($this->customer_id) && !empty($this->customer) ? $this->customer : null;
        $blParams = ['checkZone' => EmailBlacklist::CHECK_ZONE_TRANSACTIONAL_EMAILS];
        if (EmailBlacklist::isBlacklisted($this->to_email, null, $customer, $blParams)) {
            $this->addError('to_email', t('transactional_emails', 'This email address is blacklisted!'));
            return false;
        }
        return parent::beforeSave();
    }
}
