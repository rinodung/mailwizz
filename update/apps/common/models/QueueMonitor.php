<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * QueueMonitor
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

/**
 * This is the model class for table "{{queue_monitor}}".
 *
 * The followings are the available columns in table '{{queue_monitor}}':
 * @property string $id
 * @property string $message_id
 * @property string $queue
 * @property integer|string $user_id
 * @property integer|string $customer_id
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property User $user
 */
class QueueMonitor extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{queue_monitor}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['message_id', 'length', 'is' => 36],
            ['user_id, customer_id', 'numerical', 'integerOnly' => true],
            ['queue', 'length', 'max' => 255],
            ['status', 'length', 'max' => 30],

            ['id, queue, user_id, customer_id, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'customer'  => [self::BELONGS_TO, 'Customer', 'customer_id'],
            'user'      => [self::BELONGS_TO, 'User', 'user_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'id'            => t('queue', 'ID'),
            'message_id'    => t('queue', 'Message ID'),
            'queue'         => t('queue', 'Queue'),
            'user_id'       => t('queue', 'User'),
            'customer_id'   => t('queue', 'Customer'),
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
                    'condition' => '
                        customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name
                    ',
                    'params' => [':name' => '%' . $customerId . '%'],
                ];
            }
        }

        if (!empty($this->user_id)) {
            $userId = (string)$this->user_id;
            if (is_numeric($userId)) {
                $criteria->compare('t.user_id', $userId);
            } else {
                $criteria->with['user'] = [
                    'condition' => '
                        user.email LIKE :name OR user.first_name LIKE :name OR user.last_name LIKE :name
                    ',
                    'params' => [':name' => '%' . $userId . '%'],
                ];
            }
        }

        $criteria->compare('id', $this->id, true);
        $criteria->compare('message_id', $this->message_id, true);
        $criteria->compare('queue', $this->queue, true);
        $criteria->compare('status', $this->status, true);

        return new CActiveDataProvider($this, [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.date_added'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return QueueMonitor the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var QueueMonitor $parent */
        $parent = parent::model($className);

        return $parent;
    }

    /**
     * @return array
     */
    public function getStatusesList(): array
    {
        return [
            QueueStatus::ACK                  => t('queue', 'Acknowledged'),
            QueueStatus::REJECT               => t('queue', 'Reject'),
            QueueStatus::REQUEUE              => t('queue', 'Requeue'),
            QueueStatus::ALREADY_ACKNOWLEDGED => t('queue', 'Already acknowledged'),
            QueueStatus::WAITING              => t('queue', 'Waiting'),
            QueueStatus::PROCESSING           => t('queue', 'Processing'),
        ];
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return $this->status === QueueStatus::PROCESSING && ((time() - (int)strtotime($this->date_added)) > 24 * 60 * 60);
    }
}
