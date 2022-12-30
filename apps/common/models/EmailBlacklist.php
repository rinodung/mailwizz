<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklist
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "email_blacklist".
 *
 * The followings are the available columns in table 'email_blacklist':
 * @property integer $email_id
 * @property integer|null $subscriber_id
 * @property string $email
 * @property string $reason
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class EmailBlacklist extends ActiveRecord
{
    /**
     * flag for emails that are check when a campaign is sent
     */
    const CHECK_ZONE_CAMPAIGN = 'campaign sending';

    /**
     * flag for emails that are check when a subscriber is added in a list
     */
    const CHECK_ZONE_LIST_SUBSCRIBE = 'list subscribe';

    /**
     * flag for emails that are check when a subscriber is imported in a list
     */
    const CHECK_ZONE_LIST_IMPORT = 'list import';

    /**
     * flag for emails that are check when a subscriber is exported from a list
     */
    const CHECK_ZONE_LIST_EXPORT = 'list export';

    /**
     * flag for emails that are check when transactional email is sent
     */
    const CHECK_ZONE_TRANSACTIONAL_EMAILS = 'transactional emails sending';

    /**
     * @var CUploadedFile $file - the uploaded file for import
     */
    public $file;

    /**
     * store email => bool (whether is blacklisted or not)
     *
     * @var array
     */
    protected static $emailsStore = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{email_blacklist}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var OptionImporter $optionImporter */
        $optionImporter = container()->get(OptionImporter::class);

        $mimes = null;
        if ($optionImporter->getCanCheckMimeType()) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $mimes */
            $mimes = $extensionMimes->get('csv')->toArray();
        }

        $rules = [
            ['email', 'required', 'on' => 'insert, update'],
            ['email', 'length', 'max' => 150],
            ['email', '_validateEmail'],
            ['email', '_validateEmailUnique'],

            ['reason', 'safe'],
            ['email', 'safe', 'on' => 'search'],

            ['email, reason', 'unsafe', 'on' => 'import'],
            ['file', 'required', 'on' => 'import'],
            ['file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => 512000000, 'allowEmpty' => true],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'email_id'      => t('email_blacklist', 'Email'),
            'subscriber_id' => t('email_blacklist', 'Subscriber'),
            'email'         => t('email_blacklist', 'Email'),
            'reason'        => t('email_blacklist', 'Reason'),
        ];

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
        $criteria->compare('email', $this->email, true);
        $criteria->compare('reason', $this->reason, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder'  => [
                    'email_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EmailBlacklist the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var EmailBlacklist $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     * @throws CDbException
     */
    public function delete()
    {
        // when taken out of blacklist remove all the log records
        // NOTE: when a subscriber is deleted the column subscriber_id gets nulled so that we keep
        // the blacklist email for future additions.
        if (!empty($this->subscriber_id)) {
            try {
                $attributes = ['subscriber_id' => (int)$this->subscriber_id];
                CampaignDeliveryLog::model()->deleteAllByAttributes($attributes);
                CampaignDeliveryLogArchive::model()->deleteAllByAttributes($attributes);
                CampaignBounceLog::model()->deleteAllByAttributes($attributes);
            } catch (Exception $e) {
            }
        }

        // since 1.3.5.9 - mark back as confirmed
        try {
            $criteria = new CDbCriteria();
            $criteria->compare('status', ListSubscriber::STATUS_BLACKLISTED);

            /** @var OptionEmailBlacklist $emailBlacklistOptions */
            $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

            if (!$emailBlacklistOptions->getAllowMd5()) {
                $criteria->addCondition('email = :e');
                $criteria->params[':e'] = $this->email;
            } else {
                if (StringHelper::isMd5($this->email)) {
                    $criteria->addCondition('(email = :e OR MD5(email) = :e)');
                    $criteria->params[':e'] = $this->email;
                } else {
                    $criteria->addCondition('(email = :e OR email = :m)');
                    $criteria->params[':e'] = $this->email;
                    $criteria->params[':m'] = md5($this->email);
                }
            }

            ListSubscriber::model()->updateAll([
                'status' => ListSubscriber::STATUS_CONFIRMED,
            ], $criteria);
        } catch (Exception $e) {
        }

        // delete from store
        self::deleteFromStore($this->email);

        return parent::delete();
    }

    /**
     * @param mixed $subscriber
     * @param string $reason
     *
     * @return bool|mixed
     * @throws CException
     */
    public static function addToBlacklist($subscriber, string $reason = '')
    {
        // since 1.4.5
        if (is_object($subscriber) && $subscriber instanceof ListSubscriber && !$subscriber->getIsConfirmed()) {
            return false;
        }

        // since 1.3.6.2
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_NEW_BLACKLIST_RECORDS') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_NEW_BLACKLIST_RECORDS
        ) {
            if (is_object($subscriber) && $subscriber instanceof ListSubscriber && !empty($subscriber->subscriber_id)) {
                if (!$subscriber->getIsConfirmed()) {
                    return false;
                }
                $subscriber->saveStatus(ListSubscriber::STATUS_BLACKLISTED);
                return true;
            }
            return false;
        }

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        // since 1.3.9.3
        if (!$emailBlacklistOptions->getAllowNewRecords()) {
            if (is_object($subscriber) && $subscriber instanceof ListSubscriber && !empty($subscriber->subscriber_id)) {
                if (!$subscriber->getIsConfirmed()) {
                    return false;
                }
                $subscriber->saveStatus(ListSubscriber::STATUS_BLACKLISTED);
                return true;
            }
            return false;
        }

        $email = $subscriber_id = null;

        if (is_object($subscriber) && $subscriber instanceof ListSubscriber && !empty($subscriber->subscriber_id)) {
            $subscriber_id = (int)$subscriber->subscriber_id;
            $email         = $subscriber->email;
        } elseif (is_string($subscriber)) {
            $email = $subscriber;
        } else {
            return false;
        }

        if (($data = self::getFromStore($email)) && is_array($data)) {
            return $data['blacklisted'];
        }

        $exists = self::model()->findByAttributes(['email' => $email]);
        if (!empty($exists)) {
            self::addToStore($email, [
                'blacklisted' => true,
                'reason'      => $exists->reason,
            ]);
            if (
                is_object($subscriber) &&
                $subscriber instanceof ListSubscriber &&
                !empty($subscriber->subscriber_id) &&
                $subscriber->getIsConfirmed()
            ) {
                $subscriber->saveStatus(ListSubscriber::STATUS_BLACKLISTED);
            }
            return true;
        }

        // since 1.3.5.9
        $customer = null;
        try {
            if (app()->hasComponent('customer') && customer()->getId() > 0) {
                $customer = customer()->getModel();
            }
            if (empty($customer) && !empty($subscriber) && !empty($subscriber->list)) {
                $customer = $subscriber->list->customer;
            }
        } catch (Exception $e) {
            $customer = null;
        }
        //

        // since 1.3.5.9
        hooks()->doAction('email_blacklist_before_add_email_to_blacklist', $collection = new CAttributeCollection([
            'email'    => $email,
            'customer' => $customer,
            'continue' => true,
        ]));
        if (!$collection->itemAt('continue')) {
            hooks()->doAction('email_blacklist_after_add_email_to_blacklist', new CAttributeCollection([
                'email'    => $email,
                'saved'    => false,
                'customer' => $customer,
            ]));
            return false;
        }
        //

        $saved = false;
        try {
            $model = new self();
            $model->email         = $email;
            $model->subscriber_id = !empty($subscriber_id) ? (int)$subscriber_id : null;
            $model->reason        = $reason;
            $saved = $model->save(false);
        } catch (Exception $e) {
        }

        if ($saved) {
            self::addToStore($email, [
                'blacklisted' => true,
                'reason'      => $reason,
            ]);
        }

        // since 1.3.5.9
        hooks()->doAction('email_blacklist_after_add_email_to_blacklist', new CAttributeCollection([
            'email'    => $email,
            'saved'    => $saved,
            'customer' => $customer,
        ]));

        return (bool)$saved;
    }

    /**
     * @param string $email
     * @param ListSubscriber|null $subscriber
     * @param Customer|null $customer
     * @param array $params
     *
     * Return boolean false means the email is not blacklisted, anything else means the email is blacklisted.
     * Please keep in mind that since 1.3.6.2 if false is not returned, then a EmailBlacklistCheckInfo object will be returned
     *
     * @return bool|EmailBlacklistCheckInfo
     * @throws CException
     */
    public static function isBlacklisted(string $email, ?ListSubscriber $subscriber = null, ?Customer $customer = null, array $params = [])
    {
        // since 1.3.6.2
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK
        ) {
            return false;
        }

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        if (!$emailBlacklistOptions->getLocalCheck()) {
            return false;
        }

        if (($data = self::getFromStore($email)) && is_array($data)) {
            return !empty($data['blacklisted']) ? new EmailBlacklistCheckInfo($data) : false;
        }

        // @since 2.0.29
        if (DomainBlacklist::isEmailBlacklisted($email)) {
            $domain = EmailHelper::getDomainFromEmail($email);
            self::addToStore($email, $blCheckInfo = [
                'email'       => $email,
                'blacklisted' => true,
                'reason'      => t('email_blacklist', 'Matched blacklisted domain: {domain}', ['{domain}' => html_encode($domain)]),
            ]);
            self::addToBlacklist($email, $blCheckInfo['reason']);
            return new EmailBlacklistCheckInfo($blCheckInfo);
        }
        //

        $regularExpressions = $emailBlacklistOptions->getRegularExpressionsList();
        foreach ($regularExpressions as $regex) {
            if (@preg_match($regex, $email)) {
                self::addToStore($email, $blCheckInfo = [
                    'email'       => $email,
                    'blacklisted' => true,
                    'reason'      => t('email_blacklist', 'Matched regex: {regex}', ['{regex}' => html_encode($regex)]),
                ]);
                self::addToBlacklist($email, $blCheckInfo['reason']);
                return new EmailBlacklistCheckInfo($blCheckInfo);
            }
        }

        // 1.3.6.7
        if (!FilterVarHelper::email($email)) {
            self::addToStore($email, $blCheckInfo = [
                'email'       => $email,
                'blacklisted' => true,
                'reason'      => t('email_blacklist', 'Invalid email address format!'),
            ]);
            self::addToBlacklist($email, $blCheckInfo['reason']);
            return new EmailBlacklistCheckInfo($blCheckInfo);
        }
        //

        /**
         * AR was switched to Query Builder in this use case for performance reasons!
         * @since 1.4.4 - added md5 and sha1 checks - the $email will always be an email address
         */
        $command = db()->createCommand();
        $command->select('email_id, reason')->from('{{email_blacklist}}');

        if (!$emailBlacklistOptions->getAllowMd5()) {
            $command->where('email = :e', [
                ':e' => $email,
            ]);
        } else {
            if (StringHelper::isMd5($email)) {
                $command->where('(email = :e OR MD5(email) = :e)', [
                    ':e' => $email,
                ]);
            } else {
                $command->where('(email = :e OR email = :m)', [
                    ':e' => $email,
                    ':m' => md5($email),
                ]);
            }
        }

        $blacklisted = $command->queryRow();

        if (!empty($blacklisted)) {
            self::addToStore($email, $blCheckInfo = [
                'email'       => $email,
                'blacklisted' => true,
                'reason'      => !empty($blacklisted['reason']) ? (string)$blacklisted['reason'] : t('email_blacklist', 'Listed in the global email blacklist'),
            ]);
            unset($blacklisted);
            return new EmailBlacklistCheckInfo($blCheckInfo);
        }

        // since 1.3.5.9
        try {
            if (empty($customer) && app()->hasComponent('customer') && customer()->getId() > 0) {
                $customer = customer()->getModel();
            }
            if (empty($customer) && !empty($subscriber) && !empty($subscriber->list)) {
                $customer = $subscriber->list->customer;
            }
        } catch (Exception $e) {
            $customer = null;
        }
        //

        // return false or the reason for why blacklisted
        $blacklisted   = hooks()->applyFilters('email_blacklist_is_email_blacklisted', false, $email, $subscriber, $customer, $params);
        $isBlacklisted = (is_object($blacklisted) && $blacklisted instanceof EmailBlacklistCheckInfo) ? $blacklisted->itemAt('blacklisted') : ($blacklisted !== false);
        $bReason       = $isBlacklisted ? (string)$blacklisted : '';

        if ($isBlacklisted) {
            self::addToBlacklist($email, $bReason);
        }

        self::addToStore($email, $blCheckInfo = [
            'email'       => $email,
            'blacklisted' => $isBlacklisted,
            'reason'      => $bReason,
        ]);

        if ($isBlacklisted) {
            return new EmailBlacklistCheckInfo($blCheckInfo);
        }

        // since 1.3.6.2
        if (!empty($customer) && $customer->getGroupOption('lists.can_use_own_blacklist', 'no') == 'yes') {
            if (($data = CustomerEmailBlacklist::getFromStore((int)$customer->customer_id, $email)) && is_array($data)) {
                return !empty($data['blacklisted']) ? new EmailBlacklistCheckInfo($data) : false;
            }

            $command = db()->createCommand();
            $command->select('email_id')
                ->from('{{customer_email_blacklist}}')
                ->where('customer_id = :cid', [':cid' => $customer->customer_id]);

            if (!$emailBlacklistOptions->getAllowMd5()) {
                $command->andWhere('email = :e', [
                    ':e' => $email,
                ]);
            } else {
                if (StringHelper::isMd5($email)) {
                    $command->andWhere('(email = :e OR MD5(email) = :e)', [
                        ':e' => $email,
                    ]);
                } else {
                    $command->andWhere('(email = :e OR email = :m)', [
                        ':e' => $email,
                        ':m' => md5($email),
                    ]);
                }
            }

            $blacklisted = $command->queryRow();
            $blCheckInfo = [
                'blacklisted' => false,
                'email'       => $email,
            ];

            if (!empty($blacklisted)) {
                $blCheckInfo = [
                    'email'             => $email,
                    'reason'            => 'Found in customer suppression list!',
                    'blacklisted'       => true,
                    'customerBlacklist' => true,
                ];
            }

            CustomerEmailBlacklist::addToStore((int)$customer->customer_id, $email, $blCheckInfo);
            unset($blacklisted);

            if (!empty($blCheckInfo['blacklisted'])) {
                return new EmailBlacklistCheckInfo($blCheckInfo);
            }
        }

        return false;
    }

    /**
     * @param string $email
     *
     * @return EmailBlacklist|null
     */
    public function findByEmail(string $email): ?self
    {
        $criteria = new CDbCriteria();

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        if (!$emailBlacklistOptions->getAllowMd5()) {
            $criteria->addCondition('email = :e');
            $criteria->params[':e'] = $email;
        } else {
            if (StringHelper::isMd5($email)) {
                $criteria->addCondition('(email = :e OR MD5(email) = :e)');
                $criteria->params[':e'] = $email;
            } else {
                $criteria->addCondition('(email = :e OR email = :m)');
                $criteria->params[':e'] = $email;
                $criteria->params[':m'] = md5($email);
            }
        }

        return self::model()->find($criteria);
    }

    /**
     * @param string $email
     *
     * @return bool
     * @throws CDbException
     */
    public static function removeByEmail(string $email): bool
    {
        if (!($model = self::model()->findByEmail($email))) {
            return false;
        }
        return (bool)$model->delete();
    }

    /**
     * @param string $email
     * @param array $storeData
     *
     * @return bool
     */
    public static function addToStore(string $email, array $storeData = []): bool
    {
        if (!isset($storeData['blacklisted'])) {
            return false;
        }
        self::$emailsStore[$email] = $storeData;
        return true;
    }

    /**
     * @param string $email
     * @return bool|mixed
     */
    public static function getFromStore(string $email)
    {
        return self::$emailsStore[$email] ?? false;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public static function deleteFromStore(string $email): bool
    {
        if (isset(self::$emailsStore[$email])) {
            unset(self::$emailsStore[$email]);
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public static function getCheckZones(): array
    {
        return [
            self::CHECK_ZONE_CAMPAIGN,
            self::CHECK_ZONE_LIST_IMPORT,
            self::CHECK_ZONE_LIST_SUBSCRIBE,
            self::CHECK_ZONE_LIST_EXPORT, // This is not actually used
            self::CHECK_ZONE_TRANSACTIONAL_EMAILS,
        ];
    }

    /**
     * @param array $insert
     *
     * @return int
     * @throws CException
     */
    public static function insertMultipleUnique(array $insert): int
    {
        $inserted = 0;

        if (empty($insert)) {
            return $inserted;
        }

        $mutexKey = __FILE__ . ':' . __METHOD__;
        if (!mutex()->acquire($mutexKey, 60)) {
            throw new Exception('Unable to acquire the mutex to process the file!');
        }

        // make sure we have no duplicates inside the array itself
        $insert = collect($insert)->map(function ($item) {
            $item['email'] = strtolower((string)$item['email']);
            return $item;
        })->unique('email', true)
            ->reject(function ($item) {
                return !FilterVarHelper::email($item['email']) && !StringHelper::isMd5($item['email']);
            })->all();

        // query the database to get all existing data
        $rows = db()->createCommand()
            ->select('LOWER(email) as email')
            ->from('{{email_blacklist}}')
            ->andWhere(['in', 'email', array_column($insert, 'email')])
            ->limit(count($insert))
            ->queryColumn();

        // remove the saved hashes from the insert array
        $insert = collect($insert)->reject(function ($item) use (&$rows) {
            return in_array($item['email'], $rows, true);
        })->all();

        // what we have left is just records not in database.
        if (empty($insert)) {
            mutex()->release($mutexKey);
            return $inserted;
        }

        try {
            $builder = db()->getSchema()->getCommandBuilder();
            $inserted += (int)$builder->createMultipleInsertCommand('{{email_blacklist}}', $insert)->execute();
        } catch (Exception $e) {
            mutex()->release($mutexKey);
            throw $e;
        }

        mutex()->release($mutexKey);
        return $inserted;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateEmailUnique(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        $criteria = new CDbCriteria();
        $criteria->addCondition('email_id != :i');
        $criteria->params[':i'] = (int)$this->email_id;

        if (!$emailBlacklistOptions->getAllowMd5()) {
            $criteria->addCondition('email = :e');
            $criteria->params[':e'] = (string)$this->$attribute;
        } else {
            if (StringHelper::isMd5((string)$this->$attribute)) {
                $criteria->addCondition('(email = :e OR MD5(email) = :e)');
                $criteria->params[':e'] = (string)$this->$attribute;
            } else {
                $criteria->addCondition('(email = :e OR email = :m)');
                $criteria->params[':e'] = (string)$this->$attribute;
                $criteria->params[':m'] = md5((string)$this->$attribute);
            }
        }

        $duplicate = self::model()->find($criteria);

        if (!empty($duplicate)) {
            $this->addError('email', t('email_blacklist', 'The email address {email} is already in your blacklist!', [
                '{email}' => $this->$attribute,
            ]));
            return;
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateEmail(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        if (FilterVarHelper::email($this->$attribute)) {
            return;
        }

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        if ($emailBlacklistOptions->getAllowMd5() && StringHelper::isMd5($this->$attribute)) {
            return;
        }

        $this->addError($attribute, t('email_blacklist', 'Please enter a valid email address!'));
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord()) {
            if (
                (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
                defined('MW_PERF_LVL_DISABLE_NEW_BLACKLIST_RECORDS') &&
                MW_PERF_LVL & MW_PERF_LVL_DISABLE_NEW_BLACKLIST_RECORDS
            ) {
                return false;
            }

            /** @var OptionEmailBlacklist $emailBlacklistOptions */
            $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

            // since 1.3.9.3
            if (!$emailBlacklistOptions->getAllowNewRecords()) {
                return false;
            }
        }

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        // since 1.3.5
        if (!empty($this->email)) {
            try {
                $criteria = new CDbCriteria();
                $criteria->compare('status', ListSubscriber::STATUS_CONFIRMED);

                /** @var OptionEmailBlacklist $emailBlacklistOptions */
                $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

                if (!$emailBlacklistOptions->getAllowMd5()) {
                    $criteria->addCondition('email = :e');
                    $criteria->params[':e'] = $this->email;
                } else {
                    if (StringHelper::isMd5($this->email)) {
                        $criteria->addCondition('(email = :e OR MD5(email) = :e)');
                        $criteria->params[':e'] = $this->email;
                    } else {
                        $criteria->addCondition('(email = :e OR email = :m)');
                        $criteria->params[':e'] = $this->email;
                        $criteria->params[':m'] = md5($this->email);
                    }
                }

                ListSubscriber::model()->updateAll([
                    'status' => ListSubscriber::STATUS_BLACKLISTED,
                ], $criteria);
            } catch (Exception $e) {
            }
        }

        parent::afterSave();
    }
}
