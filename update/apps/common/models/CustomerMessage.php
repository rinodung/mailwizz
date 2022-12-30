<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerMessage
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

/**
 * This is the model class for table "customer_message".
 *
 * The followings are the available columns in table 'customer_message':
 * @property integer $message_id
 * @property string $message_uid
 * @property integer|string $customer_id
 * @property string $title
 * @property string $message
 * @property mixed $title_translation_params
 * @property mixed $message_translation_params
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerMessage extends ActiveRecord
{
    /**
     * Flags for various statuses
     */
    const STATUS_UNSEEN = 'unseen';
    const STATUS_SEEN = 'seen';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_message}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['customer_id, message', 'required'],
            ['customer_id', 'exist', 'className' => Customer::class],
            ['title', 'length', 'max' => 255],
            ['message', 'length', 'min' => 5],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            // The following rule is used by search().
            ['customer_id, title, message, status', 'safe', 'on'=>'search'],
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
            'message_id'        => t('messages', 'Message'),
            'message_uid'       => t('messages', 'Message'),
            'customer_id'       => t('messages', 'Customer'),
            'title'		        => t('messages', 'Title'),
            'message' 	        => t('messages', 'Message'),

            'translatedTitle'   => t('messages', 'Title'),
            'translatedMessage' => t('messages', 'Message'),
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
        $criteria->with = [];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $customerId . '%'],
                ];
            }
        }

        $criteria->compare('t.title', $this->title, true);
        $criteria->compare('t.message', $this->message, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.message_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.message_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerMessage the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerMessage $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslatedTitle(): string
    {
        if (!empty($this->title_translation_params) && is_array($this->title_translation_params)) {
            return t('messages', $this->title, $this->title_translation_params);
        }

        return (string)$this->title;
    }

    /**
     * @return string
     */
    public function getTranslatedMessage(): string
    {
        if (!empty($this->message_translation_params) && is_array($this->message_translation_params)) {
            return t('messages', $this->message, $this->message_translation_params);
        }

        return (string)$this->message;
    }

    /**
     * @param string $message_uid
     *
     * @return CustomerMessage|null
     */
    public function findByUid(string $message_uid): ?self
    {
        return self::model()->findByAttributes([
            'message_uid' => $message_uid,
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
        return (string)$this->message_uid;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_UNSEEN => t('messages', 'Unseen'),
            self::STATUS_SEEN   => t('messages', 'Seen'),
        ];
    }

    /**
     * @param int $length
     * @return string
     */
    public function getShortMessage(int $length = 45): string
    {
        return StringHelper::truncateLength($this->getTranslatedMessage(), $length);
    }

    /**
     * @param int $length
     * @return string
     */
    public function getShortTitle(int $length = 25): string
    {
        return StringHelper::truncateLength($this->getTranslatedTitle(), $length);
    }

    /**
     * @return bool
     */
    public function getIsUnseen(): bool
    {
        return $this->getStatusIs(self::STATUS_UNSEEN);
    }

    /**
     * @return bool
     */
    public function getIsSeen(): bool
    {
        return $this->getStatusIs(self::STATUS_SEEN);
    }

    /**
     * @param int $customerId
     */
    public static function markAllAsSeenForCustomer(int $customerId): void
    {
        $attributes = ['status' => self::STATUS_SEEN];
        $instance   = new self();
        db()->createCommand()->update($instance->tableName(), $attributes, 'customer_id = :id', [':id' => (int)$customerId]);
    }

    /**
     * @return void
     */
    public function broadcast(): void
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'customer_id';
        $criteria->compare('status', Customer::STATUS_ACTIVE);

        CustomerCollection::findAll($criteria)->each(function (Customer $customer) {
            $message = clone $this;
            $message->customer_id   = (int)$customer->customer_id;
            $message->date_added    = MW_DATETIME_NOW;
            $message->last_updated  = MW_DATETIME_NOW;
            $message->save();
        });
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if ($this->getIsNewRecord()) {
            $this->message_uid = $this->generateUid();
        }

        if (!empty($this->title_translation_params)) {
            $this->title_translation_params = serialize($this->title_translation_params);
        }

        if (!empty($this->message_translation_params)) {
            $this->message_translation_params = serialize($this->message_translation_params);
        }

        return true;
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        parent::afterFind();

        if (!empty($this->title_translation_params)) {
            $this->title_translation_params = unserialize((string)$this->title_translation_params);
        }

        if (!empty($this->message_translation_params)) {
            $this->message_translation_params = unserialize((string)$this->message_translation_params);
        }
    }
}
