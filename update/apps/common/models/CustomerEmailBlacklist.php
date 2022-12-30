<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerEmailBlacklist
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

/**
 * This is the model class for table "customer_suppression_list".
 *
 * The followings are the available columns in table 'customer_suppression_list':
 * @property integer $email_id
 * @property string $email_uid
 * @property integer $customer_id
 * @property string $email
 * @property string $reason
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerEmailBlacklist extends ActiveRecord
{
    /**
     * @var CUploadedFile $file - uploaded file containing the suppressed emails
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
        return '{{customer_email_blacklist}}';
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
        $relations = [
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];
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
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->compare('email', $this->email, true);

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
     * @return CustomerEmailBlacklist the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerEmailBlacklist $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     * @throws CDbException
     */
    public function delete()
    {
        try {
            /** @var OptionEmailBlacklist $emailBlacklistOptions */
            $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

            $criteria = new CDbCriteria();
            $criteria->addInCondition('list_id', $this->customer->getAllListsIds());
            $criteria->compare('status', ListSubscriber::STATUS_BLACKLISTED);

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
        self::deleteFromStore((int)$this->customer_id, $this->email);

        return parent::delete();
    }

    /**
     * @param string $email_uid
     *
     * @return CustomerEmailBlacklist|null
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
     * @param string $email
     *
     * @return CustomerEmailBlacklist|null
     */
    public function findByEmail(string $email): ?self
    {
        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        $criteria = new CDbCriteria();

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
     * @param int $customerId
     * @return CustomerEmailBlacklist|null
     */
    public static function findByEmailWithCustomerId(string $email, int $customerId): ?self
    {
        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', $customerId);

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
     * @param int $customerId
     * @param string $email
     * @param array $storeData
     *
     * @return bool
     */
    public static function addToStore(int $customerId, string $email, array $storeData = []): bool
    {
        if (!isset($storeData['blacklisted'])) {
            return false;
        }
        if (!isset(self::$emailsStore[$customerId])) {
            self::$emailsStore[$customerId] = [];
        }
        self::$emailsStore[$customerId][$email] = $storeData;
        return true;
    }

    /**
     * @param int $customerId
     * @param string $email
     * @return mixed
     */
    public static function getFromStore(int $customerId, string $email)
    {
        if (!isset(self::$emailsStore[$customerId])) {
            self::$emailsStore[$customerId] = [];
        }
        return self::$emailsStore[$customerId][$email] ?? false;
    }

    /**
     * @param int $customerId
     * @param string $email
     *
     * @return bool
     */
    public static function deleteFromStore(int $customerId, string $email): bool
    {
        if (!isset(self::$emailsStore[$customerId])) {
            self::$emailsStore[$customerId] = [];
        }
        if (isset(self::$emailsStore[$customerId][$email])) {
            unset(self::$emailsStore[$customerId][$email]);
            return true;
        }
        return false;
    }

    /**
     * @param string $attribute
     * @param array $params
     *
     * @return void
     */
    public function _validateEmailUnique(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if (empty($this->$attribute)) {
            return;
        }

        /** @var OptionEmailBlacklist $emailBlacklistOptions */
        $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
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
                '{email}' => (string)$this->$attribute,
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
     * @return string
     */
    public function getDisplayEmail(): string
    {
        if (apps()->isAppName('backend')) {
            return (string)$this->email;
        }

        if ($this->getIsNewRecord() || empty($this->customer_id)) {
            return (string)$this->email;
        }

        $customer = $this->customer;
        if ($customer->getGroupOption('common.mask_email_addresses', 'no') == 'yes') {
            return StringHelper::maskEmailAddress((string)$this->email);
        }

        return (string)$this->email;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (
            $this->getIsNewRecord() &&
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_CUSTOMER_NEW_BLACKLIST_RECORDS') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_CUSTOMER_NEW_BLACKLIST_RECORDS
        ) {
            return false;
        }

        if (empty($this->email_uid)) {
            $this->email_uid = $this->generateUid();
        }

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        if (!empty($this->email)) {
            try {

                /** @var OptionEmailBlacklist $emailBlacklistOptions */
                $emailBlacklistOptions = container()->get(OptionEmailBlacklist::class);

                $criteria = new CDbCriteria();
                $criteria->addInCondition('list_id', $this->customer->getAllListsIds());
                $criteria->compare('status', ListSubscriber::STATUS_CONFIRMED);

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
