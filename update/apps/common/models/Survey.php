<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Survey
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "{{survey}}".
 *
 * The followings are the available columns in table '{{survey}}':
 * @property integer|null $survey_id
 * @property string $survey_uid
 * @property integer|string $customer_id
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string|null $start_at
 * @property string|null $end_at
 * @property string $finish_redirect
 * @property string $meta_data
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property SurveyField[] $fields
 * @property SurveyField $fieldsCount
 * @property SurveyResponder[] $responders
 * @property SurveyResponder[] $respondersCount
 * @property SurveySegment[] $segments
 * @property SurveySegment[] $segmentsCount
 *
 * @property int $activeSegmentsCount
 */
class Survey extends ActiveRecord
{
    /**
     * Flag for pending-delete
     */
    const STATUS_PENDING_DELETE = 'pending-delete';

    /**
     * Flag for draft
     */
    const STATUS_DRAFT = 'draft';

    /**
     * @var array
     */
    public $copySurveyFieldsMap = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, status', 'required'],
            ['customer_id', 'numerical', 'integerOnly' => true],
            ['name, display_name', 'length', 'min' => 2, 'max' => 255],
            ['description', 'length', 'min' => 2, 'max' => 65535],
            ['start_at, end_at', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'],
            ['finish_redirect', 'url'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            ['survey_id, survey_uid, customer_id, name, display_name, description, start_at, end_at, status', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer'            => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'fields'              => [self::HAS_MANY, SurveyField::class, 'survey_id'],
            'fieldsCount'         => [self::STAT, SurveyField::class, 'survey_id'],
            'responders'          => [self::HAS_MANY, SurveyResponder::class, 'survey_id'],
            'respondersCount'     => [self::STAT, SurveyResponder::class, 'survey_id'],
            'segments'            => [self::HAS_MANY, SurveySegment::class, 'survey_id'],
            'segmentsCount'       => [self::STAT, SurveySegment::class, 'survey_id'],
            'activeSegmentsCount' => [self::STAT, SurveySegment::class, 'survey_id', 'condition' => 't.status = :s', 'params' => [':s' => SurveySegment::STATUS_ACTIVE]],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'survey_id'       => t('surveys', 'Survey'),
            'survey_uid'      => t('surveys', 'Survey'),
            'customer_id'     => t('surveys', 'Customer'),
            'name'            => t('surveys', 'Name'),
            'display_name'    => t('surveys', 'Display name'),
            'description'     => t('surveys', 'Description'),
            'start_at'        => t('surveys', 'Start at'),
            'end_at'          => t('surveys', 'End at'),
            'finish_redirect' => t('surveys', 'Finish redirect'),
            'meta_data'       => t('surveys', 'Meta data'),

            'responders_count' => t('surveys', 'Responders count'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array customized attribute help texts (name=>text)
     */
    public function attributeHelpTexts()
    {
        $text = [
            'survey_id'       => t('surveys', 'Survey'),
            'survey_uid'      => t('surveys', 'Survey'),
            'customer_id'     => t('surveys', 'Customer'),
            'name'            => t('surveys', 'The name of the survey'),
            'display_name'    => t('surveys', 'The display name of the survey which will be shown to responders. If this is left blank, the name of the survey is shown instead'),
            'description'     => t('surveys', 'The survey description shown to your responders'),
            'start_at'        => t('surveys', 'The start date since this survey will be available'),
            'end_at'          => t('surveys', 'The date when this survey will not be available anymore'),
            'finish_redirect' => t('surveys', 'Url where to redirect when the responder is reaching the survey end'),
        ];

        return CMap::mergeArray($text, parent::attributeHelpTexts());
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

        $criteria->compare('t.survey_uid', $this->survey_uid);
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.display_name', $this->display_name, true);

        if (empty($this->status)) {
            $criteria->compare('t.status', '<>' . self::STATUS_PENDING_DELETE);
        } else {
            $criteria->compare('t.status', $this->status);
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'attributes' => [
                    'survey_id',
                    'customer_id',
                    'survey_uid',
                    'name',
                    'display_name',
                    'status',
                    'date_added',
                    'last_updated',
                ],
                'defaultOrder'  => [
                    'survey_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Survey the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Survey $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'name'            => t('surveys', 'Survey name, i.e: Customer satisfaction survey.'),
            'description'     => t('surveys', 'Survey detailed description, something your responders will easily recognize.'),
            'finish_redirect' => t('surveys', 'i.e: https://www.google.com'),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @param string $survey_uid
     *
     * @return Survey|null
     */
    public function findByUid(string $survey_uid): ?self
    {
        return self::model()->findByAttributes([
            'survey_uid' => $survey_uid,
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
        return (string)$this->survey_uid;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_DRAFT    => ucfirst(t('surveys', self::STATUS_DRAFT)),
            self::STATUS_ACTIVE   => ucfirst(t('surveys', self::STATUS_ACTIVE)),
            self::STATUS_INACTIVE => ucfirst(t('surveys', self::STATUS_INACTIVE)),
        ];
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return $this->getIsRemovable();
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        if ($this->getIsPendingDelete()) {
            return false;
        }

        $removable = true;
        if (!empty($this->customer_id) && !empty($this->customer)) {
            $removable = $this->customer->getGroupOption('surveys.can_delete_own_surveys', 'yes') === 'yes';
        }
        return $removable;
    }

    /**
     * @return bool
     */
    public function getEditable(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_DRAFT]);
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_DELETE);
    }

    /**
     * @return bool
     */
    public function getIsDraft(): bool
    {
        return $this->getStatusIs(self::STATUS_DRAFT);
    }

    /**
     * @return string
     */
    public function getRespondersExportCsvFileName(): string
    {
        return sprintf('survey-responders-%s.csv', (string)$this->survey_uid);
    }


    /**
     * @return Survey|null
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
            $survey = clone $this;
            $survey->setIsNewRecord(true);
            $survey->survey_id    = null;
            $survey->survey_uid   = $this->generateUid();
            $survey->date_added   = MW_DATETIME_NOW;
            $survey->last_updated = MW_DATETIME_NOW;

            if (preg_match('/\#(\d+)$/', $survey->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $survey->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $survey->name);
            } else {
                $survey->name .= ' #1';
            }

            if (!$survey->save(false)) {
                throw new CException($survey->shortErrors->getAllAsString());
            }

            $fields = !empty($this->fields) ? $this->fields : [];
            foreach ($fields as $field) {
                $oldFieldId = (int)$field->field_id;

                $fieldOptions = !empty($field->options) ? $field->options : [];
                $field = clone $field;
                $field->setIsNewRecord(true);
                $field->field_id     = null;
                $field->survey_id      = (int)$survey->survey_id;
                $field->date_added   = MW_DATETIME_NOW;
                $field->last_updated = MW_DATETIME_NOW;
                if (!$field->save(false)) {
                    continue;
                }

                $newFieldId = (int)$field->field_id;
                $this->copySurveyFieldsMap[$oldFieldId] = $newFieldId;

                foreach ($fieldOptions as $option) {
                    $option = clone $option;
                    $option->setIsNewRecord(true);
                    $option->option_id    = null;
                    $option->field_id     = (int)$field->field_id;
                    $option->date_added   = MW_DATETIME_NOW;
                    $option->last_updated = MW_DATETIME_NOW;
                    $option->save(false);
                }
            }

            /** @var SurveySegment[] $segments */
            $segments = !empty($this->segments) ? $this->segments : [];

            foreach ($segments as $_segment) {
                if ($_segment->getIsPendingDelete()) {
                    continue;
                }

                $segment = clone $_segment;
                $segment->setIsNewRecord(true);
                $segment->survey_id    = (int)$survey->survey_id;
                $segment->segment_id   = null;
                $segment->segment_uid  = '';
                $segment->date_added   = MW_DATETIME_NOW;
                $segment->last_updated = MW_DATETIME_NOW;
                if (!$segment->save(false)) {
                    continue;
                }

                /** @var SurveySegmentCondition[] $conditions */
                $conditions = !empty($_segment->segmentConditions) ? $_segment->segmentConditions : [];
                foreach ($conditions as $_condition) {
                    if (!isset($this->copySurveyFieldsMap[$_condition->field_id])) {
                        continue;
                    }
                    $condition = clone $_condition;
                    $condition->setIsNewRecord(true);
                    $condition->condition_id = null;
                    $condition->segment_id   = (int)$segment->segment_id;
                    $condition->field_id     = $this->copySurveyFieldsMap[$_condition->field_id];
                    $condition->date_added   = MW_DATETIME_NOW;
                    $condition->last_updated = MW_DATETIME_NOW;
                    $condition->save(false);
                }
            }

            $transaction->commit();
            $copied = $survey;
            $copied->copySurveyFieldsMap = $this->copySurveyFieldsMap;
        } catch (Exception $e) {
            $transaction->rollback();
            $this->copySurveyFieldsMap = [];
        }

        /** @var Survey $copied */
        $copied = hooks()->applyFilters('models_survey_after_copy_survey', $copied, $this);

        return $copied;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getStartAt(): string
    {
        if (empty($this->start_at) || (string)$this->start_at === '0000-00-00 00:00:00') {
            return '';
        }
        return (string)$this->dateTimeFormatter->formatLocalizedDateTime((string)$this->start_at);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getEndAt(): string
    {
        if (empty($this->end_at) || (string)$this->end_at === '0000-00-00 00:00:00') {
            return '';
        }
        return (string)$this->dateTimeFormatter->formatLocalizedDateTime((string)$this->end_at);
    }

    /**
     * @return string
     */
    public function getViewUrl(): string
    {
        return apps()->getAppUrl('frontend', 'surveys/' . $this->survey_uid, true);
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return !empty($this->display_name) ? (string)$this->display_name : (string)$this->name;
    }

    /**
     * @return bool
     */
    public function getIsStarted(): bool
    {
        if (empty($this->start_at) || $this->start_at === '0000-00-00 00:00:00') {
            return true;
        }
        return strtotime($this->start_at) < time();
    }

    /**
     * @return bool
     */
    public function getIsEnded(): bool
    {
        if (empty($this->end_at) || $this->end_at === '0000-00-00 00:00:00') {
            return false;
        }
        return strtotime($this->end_at) < time();
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (empty($this->start_at) || $this->start_at === '0000-00-00 00:00:00') {
            $this->start_at = null;
        }

        if (empty($this->end_at) || $this->end_at === '0000-00-00 00:00:00') {
            $this->end_at = null;
        }

        return parent::beforeValidate();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord() && empty($this->survey_uid)) {
            $this->survey_uid = $this->generateUid();
        }

        if (empty($this->display_name)) {
            $this->display_name = $this->name;
        }

        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!$this->getIsPendingDelete()) {
            $this->saveStatus(self::STATUS_PENDING_DELETE);

            return false;
        }
        return parent::beforeDelete();
    }
}
