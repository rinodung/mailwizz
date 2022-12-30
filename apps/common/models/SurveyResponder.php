<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyResponder
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "{{survey_responder}}".
 *
 * The followings are the available columns in table '{{survey_responder}}':
 * @property integer $responder_id
 * @property string $responder_uid
 * @property integer $survey_id
 * @property integer $subscriber_id
 * @property string $ip_address
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property SurveyFieldValue[] $fieldValues
 * @property Survey $survey
 * @property ListSubscriber $subscriber
 */
class SurveyResponder extends ActiveRecord
{
    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @var array
     */
    public $surveyIds = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey_responder}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],
            ['survey_id, responder_uid, ip_address, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'fieldValues' => [self::HAS_MANY, SurveyFieldValue::class, 'responder_id'],
            'survey'      => [self::BELONGS_TO, Survey::class, 'survey_id'],
            'subscriber'  => [self::BELONGS_TO, ListSubscriber::class, 'subscriber_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'responder_id'  => t('surveys', 'Responder'),
            'responder_uid' => t('surveys', 'Responder uid'),
            'survey_id'     => t('surveys', 'Survey'),
            'subscriber_id' => t('surveys', 'Subscriber'),
            'ip_address'    => t('surveys', 'Ip address'),
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

        if (!empty($this->survey_id)) {
            $criteria->compare('t.survey_id', (int)$this->survey_id);
        } elseif (!empty($this->surveyIds)) {
            $criteria->addInCondition('t.survey_id', array_map('intval', $this->surveyIds));
        }

        $criteria->compare('t.responder_uid', $this->responder_uid);
        $criteria->compare('t.ip_address', $this->ip_address, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.responder_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.responder_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveyResponder the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveyResponder $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $responder_uid
     *
     * @return SurveyResponder|null
     */
    public function findByUid(string $responder_uid): ?self
    {
        return self::model()->findByAttributes([
            'responder_uid' => $responder_uid,
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
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return $this->getRemovable();
    }

    /**
     * @return bool
     */
    public function getCanBeEdited(): bool
    {
        return $this->getEditable();
    }

    /**
     * @return bool
     */
    public function getRemovable(): bool
    {
        $removable = true;
        if (!empty($this->survey_id) && !empty($this->survey) && !empty($this->survey->customer_id) && !empty($this->survey->customer)) {
            $removable = $this->survey->customer->getGroupOption('surveys.can_delete_own_responders', 'yes') === 'yes';
        }
        return $removable;
    }

    /**
     * @return bool
     */
    public function getEditable(): bool
    {
        $editable = true;
        if (!empty($this->survey_id) && !empty($this->survey) && !empty($this->survey->customer_id) && !empty($this->survey->customer)) {
            $editable = $this->survey->customer->getGroupOption('surveys.can_edit_own_responders', 'yes') === 'yes';
        }
        return $editable;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->responder_uid;
    }

    /**
     * @return array
     * @throws CException
     */
    public function getFullData(): array
    {
        $data = [];

        $customFields = $this->getAllCustomFieldsWithValues();

        foreach ($customFields as $key => $value) {
            $data[(string)str_replace(['[', ']'], '', $key)] = $value;
        }

        foreach (['status', 'ip_address', 'date_added'] as $key) {
            $data[strtoupper((string)$key)] = $this->$key;
        }

        if (!empty($this->subscriber_id) && !empty($this->subscriber)) {
            $data['EMAIL'] = $this->subscriber->email;
        }

        return $data;
    }

    /**
     * @return array
     *
     * @throws CException
     */
    public function loadAllCustomFieldsWithValues(): array
    {
        $fields = [];
        foreach (SurveyField::getAllBySurveyId((int)$this->survey_id) as $field) {
            $values = db()->createCommand()
                ->select('value')
                ->from('{{survey_field_value}}')
                ->where('responder_id = :sid AND field_id = :fid', [
                    ':sid' => (int)$this->responder_id,
                    ':fid' => (int)$field['field_id'],
                ])
                ->queryAll();

            $value = [];
            foreach ($values as $val) {
                $value[] = $val['value'];
            }

            $key = sprintf('%s_%d', StringHelper::getTagFromString($field['label']), $field['field_id']);
            $fields['[' . $key . ']'] = implode(', ', $value);
        }

        return $fields;
    }

    /**
     * @param bool $refresh
     *
     * @return array
     * @throws CException
     */
    public function getAllCustomFieldsWithValues(bool $refresh = false): array
    {
        static $fields = [];

        if (empty($this->responder_id)) {
            return [];
        }

        if ($refresh && isset($fields[$this->responder_id])) {
            unset($fields[$this->responder_id]);
        }

        if (isset($fields[$this->responder_id])) {
            return $fields[$this->responder_id];
        }

        $fields[$this->responder_id] = [];

        return $fields[$this->responder_id] = $this->loadAllCustomFieldsWithValues();
    }

    /**
     * @param string $field
     *
     * @return mixed
     * @throws CException
     */
    public function getCustomFieldValue(string $field)
    {
        $field  = strtoupper((string)str_replace(['[', ']'], '', $field));
        $fields = $this->getAllCustomFieldsWithValues();

        if (is_numeric($field)) {
            foreach ($fields as $key => $value) {
                if (preg_match('/' . preg_quote(sprintf('_%d]', $field), '/') . '$/', $key)) {
                    return $value;
                }
            }
            return null;
        }

        $field  = '[' . $field . ']';
        return isset($fields[$field]) || array_key_exists($field, $fields) ? $fields[$field] : null;
    }

    /**
     * @param string $ipAddress
     *
     * @return bool
     */
    public function saveIpAddress(string $ipAddress = ''): bool
    {
        if (empty($this->responder_id)) {
            return false;
        }

        if ($ipAddress && $ipAddress === (string)$this->ip_address) {
            return true;
        }

        if ($ipAddress) {
            $this->ip_address = $ipAddress;
        }
        $attributes = ['ip_address' => $this->ip_address];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;
        return (bool)db()->createCommand()->update($this->tableName(), $attributes, 'responder_id = :id', [':id' => (int)$this->responder_id]);
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->responder_uid)) {
            $this->responder_uid = $this->generateUid();
        }

        return parent::beforeSave();
    }
}
