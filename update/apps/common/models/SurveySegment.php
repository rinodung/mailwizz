<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveySegment
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "survey_segment".
 *
 * The followings are the available columns in table 'survey_segment':
 * @property integer|null $segment_id
 * @property string $segment_uid
 * @property integer|null $survey_id
 * @property string $name
 * @property string $operator_match
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Survey $survey
 * @property SurveySegmentCondition[] $segmentConditions
 */
class SurveySegment extends ActiveRecord
{
    /**
     * Operators list
     */
    const OPERATOR_MATCH_ANY = 'any';
    const OPERATOR_MATCH_ALL = 'all';

    /**
     * Status list
     */
    const STATUS_PENDING_DELETE = 'pending-delete';

    /**
     * @var array
     */
    private $_fieldConditions;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey_segment}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, operator_match', 'required'],

            ['name', 'length', 'max'=>255],
            ['operator_match', 'in', 'range'=>array_keys($this->getOperatorMatchArray())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'survey'            => [self::BELONGS_TO, Survey::class, 'survey_id'],
            'segmentConditions' => [self::HAS_MANY, SurveySegmentCondition::class, 'segment_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'segment_id'        => t('survey_segments', 'Segment'),
            'survey_id'         => t('survey_segments', 'Survey'),
            'name'              => t('survey_segments', 'Name'),
            'operator_match'    => t('survey_segments', 'Operator match'),
            'responders_count'  => t('survey_segments', 'Responders count'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a survey of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CSurveyView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', (int)$this->survey_id);

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
            'sort'=>[
                'defaultOrder' => [
                    'name'    => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveySegment the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveySegment $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int $surveyId
     *
     * @return array
     */
    public function findAllBySurveyId(int $surveyId): array
    {
        $criteria = new CDbCriteria();
        $criteria->compare('survey_id', (int)$surveyId);
        $criteria->order = 'name ASC';
        return self::model()->findAll($criteria);
    }

    /**
     * @return array
     */
    public function getOperatorMatchArray(): array
    {
        return [
            self::OPERATOR_MATCH_ANY => t('survey_segments', self::OPERATOR_MATCH_ANY),
            self::OPERATOR_MATCH_ALL => t('survey_segments', self::OPERATOR_MATCH_ALL),
        ];
    }

    /**
     * @return array
     */
    public function getFieldsDropDownArray(): array
    {
        static $_options = [];
        if (isset($_options[$this->survey_id])) {
            return $_options[$this->survey_id];
        }

        if (empty($this->survey_id)) {
            return [];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, label';
        $criteria->compare('survey_id', $this->survey_id);
        $criteria->order = 'sort_order ASC';
        $fields = SurveyField::model()->findAll($criteria);

        $options = [];

        foreach ($fields as $field) {
            $options[$field->field_id] = $field->label;
        }

        return $_options[$this->survey_id] = $options;
    }

    /**
     * @param CDbCriteria|null $extraCriteria
     * @param array $params
     *
     * @return int
     * @throws CDbException
     */
    public function countResponders(?CDbCriteria $extraCriteria = null, array $params = []): int
    {
        $criteria = $this->_createCountFindRespondersCriteria($params);
        $this->_appendCountFindRespondersCriteria($criteria);

        // this is here so that we can hook when sending the campaign.
        if ($extraCriteria) {
            $criteria->mergeWith($extraCriteria);
        }

        // since 1.3.4.9
        $criteria->select = 'COUNT(DISTINCT t.responder_id) as counter';
        $criteria->group  = '';

        return (int)SurveyResponder::model()->count($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param CDbCriteria|null $extraCriteria
     * @param array $params
     *
     * @return array
     * @throws CDbException
     */
    public function findResponders(int $offset = 0, int $limit = 10, ?CDbCriteria $extraCriteria = null, array $params = []): array
    {
        $criteria = $this->_createCountFindRespondersCriteria($params);
        $this->_appendCountFindRespondersCriteria($criteria);

        // this is here so that we can hook when sending the campaign.
        if ($extraCriteria) {
            $criteria->mergeWith($extraCriteria);
        }

        $criteria->offset = (int)$offset;
        $criteria->limit  = (int)$limit;
        return SurveyResponder::model()->findAll($criteria);
    }

    /**
     * @param string $segment_uid
     *
     * @return SurveySegment|null
     */
    public function findByUid(string $segment_uid): ?self
    {
        return self::model()->findByAttributes([
            'segment_uid' => $segment_uid,
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
        return (string)$this->segment_uid;
    }

    /**
     * @return string
     */
    public function getRespondersExportCsvFileName(): string
    {
        return sprintf('survey-responders-%s-segment-%s.csv', (string)$this->survey->survey_uid, (string)$this->segment_uid);
    }

    /**
     * @return SurveySegment|null
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
            $segment = clone $this;
            $segment->setIsNewRecord(true);
            $segment->segment_id   = null;
            $segment->segment_uid  = $this->generateUid();
            $segment->date_added   = MW_DATETIME_NOW;
            $segment->last_updated = MW_DATETIME_NOW;

            if (preg_match('/\#(\d+)$/', $segment->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $segment->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $segment->name);
            } else {
                $segment->name .= ' #1';
            }

            if (!$segment->save(false)) {
                throw new CException($segment->shortErrors->getAllAsString());
            }

            $conditions = !empty($this->segmentConditions) ? $this->segmentConditions : [];
            foreach ($conditions as $condition) {
                $condition = clone $condition;
                $condition->setIsNewRecord(true);
                $condition->condition_id = null;
                $condition->segment_id   = (int)$segment->segment_id;
                $condition->date_added   = MW_DATETIME_NOW;
                $condition->last_updated = MW_DATETIME_NOW;
                $condition->save(false);
            }

            $transaction->commit();
            $copied = $segment;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        return $copied;
    }

    /**
     * @param mixed $responder
     *
     * @return bool
     * @throws CDbException
     */
    public function hasResponder($responder): bool
    {
        if ($responder instanceof SurveyResponder) {
            $responderId = (int)$responder->responder_id;
        } else {
            $responderId = (int)$responder;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.responder_id', (int)$responderId);

        return $this->countResponders($criteria) > 0;
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete()
    {
        return $this->getStatusIs(self::STATUS_PENDING_DELETE);
    }

    /**
     * @return string
     */
    public function getUniqueIndexValue(): string
    {
        static $values = [];
        $value = StringHelper::random(6, true);
        while (isset($values[$value])) {
            $value = StringHelper::random(6, true);
        }
        $values[$value] = true;
        return $value;
    }

    /**
     * @param array $params
     * @return CDbCriteria
     * @throws CDbException
     */
    protected function _createCountFindRespondersCriteria(array $params = []): CDbCriteria
    {
        $segmentConditions = SurveySegmentCondition::model()->findAllByAttributes([
            'segment_id' => (int)$this->segment_id,
        ]);

        $criteria = new CDbCriteria();
        $criteria->compare('t.survey_id', $this->survey_id);

        if (empty($params['status']) || !is_array($params['status'])) {
            $criteria->compare('t.status', SurveyResponder::STATUS_ACTIVE);
        } else {
            $criteria->addInCondition('t.status', $params['status']);
        }

        $criteria->group = 't.responder_id';
        $criteria->order = 't.responder_id DESC';

        $fieldConditions = [];
        foreach ($segmentConditions as $segmentCondition) {
            if (!isset($fieldConditions[$segmentCondition->field_id])) {
                $fieldConditions[$segmentCondition->field_id] = [];
            }
            $fieldConditions[$segmentCondition->field_id][] = $segmentCondition;
        }

        $responder = SurveyResponder::model();
        $md = $responder->getMetaData();
        foreach ($fieldConditions as $field_id => $conditions) {
            if ($md->hasRelation('fieldValues' . $field_id)) {
                continue;
            }
            $md->addRelation('fieldValues' . $field_id, [SurveyResponder::HAS_MANY, SurveyFieldValue::class, 'responder_id']);
        }
        $this->_fieldConditions = $fieldConditions;

        unset($segmentConditions, $fieldConditions);
        return $criteria;
    }

    /**
     * @param CDbCriteria $criteria
     */
    protected function _appendCountFindRespondersCriteria(CDbCriteria $criteria): void
    {
        $fieldConditions = $this->_fieldConditions;

        $with                       = [];
        $params                     = [];
        $appendCriteriaCondition    = [];

        foreach ($fieldConditions as $field_id => $conditions) {
            $relationName    = 'fieldValues' . $field_id;
            $valueColumnName = '`fieldValues' . $field_id . '`.`value`';

            $with[$relationName] = [
                'select'    => false,
                'together'  => true,
                'joinType'  => 'LEFT JOIN',
            ];

            $conditionString = '(`fieldValues' . $field_id . '`.`field_id` = :field_id' . $field_id . ' AND (%s) )';
            $params[':field_id' . $field_id] = $field_id;

            $injectCondition = [];

            // note: since 1.3.4.7, added the is_numeric() and is_float() checks and values casting if needed
            foreach ($conditions as $condition) {
                $index = '_' . $this->getUniqueIndexValue();
                $value = $condition->getParsedValue();

                if ($condition->operator->slug === SurveySegmentOperator::IS) {
                    if (is_numeric($value)) {
                        if (is_float($value)) {
                            $injectCondition[] = 'CAST(' . $valueColumnName . ' AS DECIMAL) = :value' . $index;
                            $params[':value' . $index] = (float)$value;
                        } else {
                            $injectCondition[] = 'CAST(' . $valueColumnName . '  AS SIGNED) = :value' . $index;
                            $params[':value' . $index] = (int)$value;
                        }
                    } else {
                        $injectCondition[] = $valueColumnName . ' = :value' . $index;
                        $params[':value' . $index] = $value;
                    }
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::IS_NOT) {
                    if (is_numeric($value)) {
                        if (is_float($value)) {
                            $injectCondition[] =  'CAST(' . $valueColumnName . ' AS DECIMAL) != :value' . $index;
                            $params[':value' . $index] = (float)$value;
                        } else {
                            $injectCondition[] =  'CAST(' . $valueColumnName . '  AS SIGNED) != :value' . $index;
                            $params[':value' . $index] = (int)$value;
                        }
                    } else {
                        $injectCondition[] =  $valueColumnName . ' != :value' . $index;
                        $params[':value' . $index] = $value;
                    }
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::CONTAINS) {
                    $injectCondition[] =  $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value . '%';
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::NOT_CONTAINS) {
                    $injectCondition[] =  $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value . '%';
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::STARTS_WITH) {
                    $injectCondition[] = $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = $value . '%';
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::NOT_STARTS_WITH) {
                    $injectCondition[] = $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = $value . '%';
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::ENDS_WITH) {
                    $injectCondition[] = $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value;
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::NOT_ENDS_WITH) {
                    $injectCondition[] = $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value;
                    continue;
                }

                if ($condition->operator->slug === SurveySegmentOperator::GREATER) {
                    if (is_numeric($value)) {
                        if (is_float($value)) {
                            $injectCondition[] =  'CAST(' . $valueColumnName . ' AS DECIMAL) > :value' . $index;
                            $params[':value' . $index] = (float)$value;
                        } else {
                            $injectCondition[] =  'CAST(' . $valueColumnName . '  AS SIGNED) > :value' . $index;
                            $params[':value' . $index] = (int)$value;
                        }
                    } else {
                        $injectCondition[] =  $valueColumnName . ' > :value' . $index;
                        $params[':value' . $index] = $value;
                    }
                    continue;
                }

                if ($condition->operator->slug == SurveySegmentOperator::LESS) {
                    if (is_numeric($value)) {
                        if (is_float($value)) {
                            $injectCondition[] =  'CAST(' . $valueColumnName . ' AS DECIMAL) < :value' . $index;
                            $params[':value' . $index] = (float)$value;
                        } else {
                            $injectCondition[] =  'CAST(' . $valueColumnName . '  AS SIGNED) < :value' . $index;
                            $params[':value' . $index] = (int)$value;
                        }
                    } else {
                        $injectCondition[] =  $valueColumnName . ' < :value' . $index;
                        $params[':value' . $index] = $value;
                    }
                    continue;
                }
            }

            if (!empty($injectCondition)) {
                if ($this->operator_match === SurveySegment::OPERATOR_MATCH_ANY) {
                    $injectCondition = implode(' OR ', $injectCondition);
                } else {
                    $injectCondition = implode(' AND ', $injectCondition);
                }
                $appendCriteriaCondition[] = sprintf($conditionString, $injectCondition);
            }
        }

        if (!empty($appendCriteriaCondition)) {
            $criteria->params = array_merge($criteria->params, $params);
            if ($this->operator_match === SurveySegment::OPERATOR_MATCH_ANY) {
                $appendCondition = ' AND ' . '( ' . implode(' OR ', $appendCriteriaCondition) . ' )';
            } else {
                $appendCondition = ' AND ' . implode(' AND ', $appendCriteriaCondition);
            }

            $criteria->with = $with;
            $criteria->condition .= $appendCondition;
        } else {
            // add a condition to return nothing as a result
            $criteria->compare('t.responder_id', -1);
        }
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord() || empty($this->segment_uid)) {
            $this->segment_uid = $this->generateUid();
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
            $this->save(false);

            return false;
        }

        return parent::beforeDelete();
    }
}
