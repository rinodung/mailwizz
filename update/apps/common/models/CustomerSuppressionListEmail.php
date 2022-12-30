<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerSuppressionListEmail
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

/**
 * This is the model class for table "{{customer_suppression_list_email}}".
 *
 * The followings are the available columns in table '{{customer_suppression_list_email}}':
 * @property integer $email_id
 * @property integer $list_id
 * @property string $email
 * @property string $email_md5
 *
 * The followings are the available model relations:
 * @property CustomerSuppressionList $list
 */
class CustomerSuppressionListEmail extends ActiveRecord
{
    /**
     * Uploaded file containing the suppressed emails
     *
     * @var CUploadedFile
     */
    public $file;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_suppression_list_email}}';
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

            ['email', 'unsafe', 'on' => 'import'],
            ['file', 'required', 'on' => 'import'],
            ['file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => 512000000, 'allowEmpty' => true],

            ['email', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, CustomerSuppressionList::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'email_id'  => t('suppression_lists', 'Email'),
            'email_uid' => t('suppression_lists', 'Email'),
            'list_id'   => t('suppression_lists', 'List'),
            'email'     => t('suppression_lists', 'Email'),
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

        $criteria->compare('list_id', (int)$this->list_id);
        $criteria->order = 'email_id DESC';

        if (!empty($this->email)) {
            $criteria->addCondition('(email LIKE :e OR email_md5 LIKE :m)');
            $criteria->params[':e'] = '%' . $this->email . '%';
            $criteria->params[':m'] = '%' . $this->email . '%';
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'email_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerSuppressionListEmail the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerSuppressionListEmail $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getDisplayEmail(): string
    {
        return !empty($this->email) ? (string)$this->email : (string)$this->email_md5;
    }

    /**
     * @param ListSubscriber $subscriber
     * @param Campaign $campaign
     * @return bool
     * @throws CException
     */
    public static function isSubscriberListedByCampaign(ListSubscriber $subscriber, Campaign $campaign): bool
    {
        if ($campaign->getIsNewRecord() || $subscriber->getIsNewRecord()) {
            return false;
        }

        static $suppressionLists = [];
        if (!array_key_exists((int)$campaign->campaign_id, $suppressionLists)) {
            $lists = CustomerSuppressionListToCampaign::model()->findAllByAttributes([
                'campaign_id' => $campaign->campaign_id,
            ]);
            foreach ($lists as $list) {
                $suppressionLists[$campaign->campaign_id][] = (int)$list->list_id;
            }
        }

        if (empty($suppressionLists[$campaign->campaign_id])) {
            return false;
        }

        $lists = $suppressionLists[$campaign->campaign_id];

        // 1.5.0
        $sql = '
        SELECT COUNT(*) FROM (
            SELECT email_id, list_id FROM `{{customer_suppression_list_email}}` WHERE email IS NOT NULL AND email = :e 
            UNION 
            SELECT email_id, list_id FROM `{{customer_suppression_list_email}}` WHERE email_md5 IS NOT NULL AND email_md5 = :m
        ) t1 
        WHERE email_id != 0 AND list_id IN(' . implode(',', $lists) . ') LIMIT 1 
        ';

        $count = (int)db()->createCommand($sql)->queryScalar([
            ':e' => $subscriber->email,
            ':m' => StringHelper::md5Once($subscriber->email),
        ]);

        return $count > 0;
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

        if (empty($insert) || !isset($insert[0]['list_id'])) {
            return $inserted;
        }

        $listId   = $insert[0]['list_id'];
        $mutexKey = __FILE__ . ':' . __METHOD__ . ':' . $listId;
        if (!mutex()->acquire($mutexKey, 60)) {
            throw new Exception('Unable to acquire the mutex to process the file!');
        }

        // make sure we have no duplicates inside the array itself
        $insert = collect($insert)->map(function ($item) {
            $item['email_md5'] = strtolower((string)$item['email_md5']);
            return $item;
        })->unique('email_md5', true)
            ->reject(function ($item) {
                return !StringHelper::isMd5($item['email_md5']);
            })->all();

        // query the database to get all existing hashes saved already
        $rows = db()->createCommand()
            ->select('LOWER(email_md5) as email_md5')
            ->from('{{customer_suppression_list_email}}')
            ->where('list_id = :id', [':id' => $listId])
            ->andWhere(['in', 'email_md5', array_column($insert, 'email_md5')])
            ->limit(count($insert))
            ->queryColumn();

        // remove the saved hashes from the insert array
        $insert = collect($insert)->reject(function ($item) use (&$rows) {
            return in_array($item['email_md5'], $rows, true);
        })->all();

        // what we have left is just records not in database.
        if (empty($insert)) {
            mutex()->release($mutexKey);
            return $inserted;
        }

        try {
            $builder = db()->getSchema()->getCommandBuilder();
            $inserted += (int)$builder->createMultipleInsertCommand('{{customer_suppression_list_email}}', $insert)->execute();
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
     *
     * @throws CException
     */
    public function _validateEmailUnique(string $attribute, array $params = []): void
    {
        if ($this->hasErrors() || empty($this->$attribute)) {
            return;
        }

        // 1.5.0
        $sql = '
        SELECT COUNT(*) FROM (
            SELECT email_id, list_id FROM `{{customer_suppression_list_email}}` WHERE email IS NOT NULL AND email = :e 
            UNION 
            SELECT email_id, list_id FROM `{{customer_suppression_list_email}}` WHERE email_md5 IS NOT NULL AND email_md5 = :m
        ) t1 
        WHERE email_id != :eid AND list_id = :lid LIMIT 1
        ';

        $count = (int)db()->createCommand($sql)->queryScalar([
            ':e'   => $this->$attribute,
            ':m'   => StringHelper::md5Once($this->$attribute),
            ':lid' => (int)$this->list->list_id,
            ':eid' => (int)$this->email_id,
        ]);

        if ($count > 0) {
            $this->addError('email', t('suppression_lists', 'The email address {email} is already in your suppression list!', [
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

        if (StringHelper::isMd5($this->$attribute)) {
            return;
        }

        $this->addError($attribute, t('suppression_lists', 'Please enter a valid email address!'));
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!empty($this->email) && StringHelper::isMd5($this->email)) {
            $this->email_md5 = $this->email;
            $this->email     = '';
        }

        if (!empty($this->email) && (empty($this->email_md5) || !StringHelper::isMd5($this->email_md5))) {
            $this->email_md5 = StringHelper::md5Once($this->email);
        }

        if (empty($this->email) && empty($this->email_md5)) {
            return false;
        }

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        if (empty($this->email) && !empty($this->email_md5)) {
            $this->email = $this->email_md5;
        }
        parent::afterFind();
    }
}
