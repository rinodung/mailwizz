<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerNote
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.8
 */

/**
 * This is the model class for table "customer_note".
 *
 * The followings are the available columns in table 'customer_note':
 * @property integer $note_id
 * @property string $note_uid
 * @property integer $user_id
 * @property integer $customer_id
 * @property string $title
 * @property string $note
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property User $user
 * @property Customer $customer
 */
class CustomerNote extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{customer_note}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        $rules = [
            ['customer_id, note', 'required'],
            ['customer_id', 'exist', 'className' => 'Customer'],
            ['title', 'length', 'max' => 255],
            ['note', 'length', 'min' => 5, 'max' => 65535],

            // The following rule is used by search().
            ['customer_id, user_id, title', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        $relations = [
            'customer' => [self::BELONGS_TO, 'Customer', 'customer_id'],
            'user'     => [self::BELONGS_TO, User::class, 'user_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $labels = [
            'note_id'        => t('notes', 'Note'),
            'note_uid'       => t('notes', 'Note'),
            'user_id'        => t('notes', 'Created by'),
            'customer_id'    => t('notes', 'Customer'),
            'title'		     => t('notes', 'Title'),
            'note' 	         => t('notes', 'Note'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'note' => t('notes', 'Your note contents. Please keep in mind that it will be encrypted'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
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
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        if (!empty($this->user_id)) {
            if (is_numeric($this->user_id)) {
                $criteria->compare('t.user_id', $this->user_id);
            } else {
                $criteria->with['user'] = [
                    'condition' => 'user.email LIKE :name OR user.first_name LIKE :name OR user.last_name LIKE :name',
                    'params'    => [':name' => '%' . $this->user_id . '%'],
                ];
            }
        }

        if (!empty($this->customer_id)) {
            if (is_numeric($this->customer_id)) {
                $criteria->compare('t.customer_id', $this->customer_id);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $this->customer_id . '%'],
                ];
            }
        }

        $criteria->compare('t.title', $this->title, true);

        $criteria->order = 't.note_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=> [
                'defaultOrder' => [
                    't.note_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerNote the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerNote $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $note_uid
     * @return $this|null
     */
    public function findByUid(string $note_uid): ?self
    {
        return self::model()->findByAttributes([
            'note_uid' => $note_uid,
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
        return (string)$this->note_uid;
    }

    /**
     * @param int $length
     * @return string
     */
    public function getShortTitle(int $length = 25): string
    {
        return StringHelper::truncateLength($this->title, $length);
    }

    /**
     * @inheritDoc
     */
    protected function afterFind()
    {
        parent::afterFind();

        if (!empty($this->note)) {
            $this->note = StringHelper::decrypt($this->note, $this->customer->customer_uid);
        }
    }

    /**
     * @inheritdoc
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if ($this->isNewRecord) {
            $this->note_uid = $this->generateUid();
        }

        if (!empty($this->note)) {
            $customer   = Customer::model()->findByPk($this->customer_id);
            $this->note = StringHelper::encrypt($this->note, $customer->customer_uid);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function afterSave()
    {
        parent::afterSave();

        if (!empty($this->note)) {
            $customer   = Customer::model()->findByPk($this->customer_id);
            $this->note = StringHelper::decrypt($this->note, $customer->customer_uid);
        }
    }
}
