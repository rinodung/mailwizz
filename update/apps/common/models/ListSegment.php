<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSegment
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_segment".
 *
 * The followings are the available columns in table 'list_segment':
 * @property integer|null $segment_id
 * @property string $segment_uid
 * @property integer $list_id
 * @property string $name
 * @property string $operator_match
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 * @property Lists $list
 * @property ListSegmentCondition[] $segmentConditions
 */
class ListSegment extends ActiveRecord
{
    /**
     * Operator flags
     */
    const OPERATOR_MATCH_ANY = 'any';
    const OPERATOR_MATCH_ALL = 'all';

    /**
     * Status flags
     */
    const STATUS_PENDING_DELETE = 'pending-delete';

    /**
     * The pattern used for subscribers count
     */
    const SUBSCRIBERS_COUNTER_KEY_PATTERN = 'customer.lists.%d.segments.%d.counter.subscribers';

    /**
     * @var array
     */
    private $_fieldConditions;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_segment}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, operator_match', 'required'],

            ['name', 'length', 'max'=>255],
            ['operator_match', 'in', 'range' => array_keys($this->getOperatorMatchArray())],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaigns'        => [self::HAS_MANY, Campaign::class, 'segment_id'],
            'list'             => [self::BELONGS_TO, Lists::class, 'list_id'],
            'segmentConditions'=> [self::HAS_MANY, ListSegmentCondition::class, 'segment_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'segment_id'        => t('list_segments', 'Segment'),
            'list_id'           => t('list_segments', 'List'),
            'name'              => t('list_segments', 'Name'),
            'operator_match'    => t('list_segments', 'Operator match'),
            'subscribers_count' => t('list_segments', 'Subscribers count'),
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
        $criteria->compare('t.list_id', (int)$this->list_id);

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
     * @return ListSegment the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSegment $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int $listId
     *
     * @return array
     */
    public function findAllByListId(int $listId): array
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$listId);
        $criteria->order = 'name ASC';
        return self::model()->findAll($criteria);
    }

    /**
     * @return array
     */
    public function getOperatorMatchArray(): array
    {
        return [
            self::OPERATOR_MATCH_ANY => t('list_segments', self::OPERATOR_MATCH_ANY),
            self::OPERATOR_MATCH_ALL => t('list_segments', self::OPERATOR_MATCH_ALL),
        ];
    }

    /**
     * @return array
     */
    public function getFieldsDropDownArray(): array
    {
        static $_options = [];
        if (isset($_options[$this->list_id])) {
            return $_options[$this->list_id];
        }

        if (empty($this->list_id)) {
            return [];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, label';
        $criteria->compare('list_id', $this->list_id);
        $criteria->order = 'sort_order ASC';
        $fields = ListField::model()->findAll($criteria);

        $options = [];

        foreach ($fields as $field) {
            $options[$field->field_id] = $field->label;
        }

        return $_options[$this->list_id] = $options;
    }

    /**
     * @param CDbCriteria|null $extraCriteria
     * @param array $params
     *
     * @return int
     * @throws CDbException
     */
    public function countSubscribers(?CDbCriteria $extraCriteria = null, array $params = []): int
    {
        $criteria = $this->_createCountFindSubscribersCriteria($params);
        $this->_appendCountFindSubscribersCriteria($criteria);

        // this is here so that we can hook when sending the campaign.
        if ($extraCriteria) {
            $criteria->mergeWith($extraCriteria);
        }

        // since 1.3.4.9
        $criteria->select = 'COUNT(DISTINCT t.subscriber_id) as counter';
        $criteria->group  = '';

        return (int)ListSubscriber::model()->count($criteria);
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
    public function findSubscribers(int $offset = 0, int $limit = 10, ?CDbCriteria $extraCriteria = null, array $params = []): array
    {
        $criteria = $this->_createCountFindSubscribersCriteria($params);
        $this->_appendCountFindSubscribersCriteria($criteria);

        // this is here so that we can hook when sending the campaign.
        if (!empty($extraCriteria) && $extraCriteria instanceof CDbCriteria) {
            $criteria->mergeWith($extraCriteria);
        }

        $criteria->offset = (int)$offset;
        $criteria->limit  = (int)$limit;
        return ListSubscriber::model()->findAll($criteria);
    }

    /**
     * @param string $segment_uid
     *
     * @return ListSegment|null
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
    public function getSubscribersExportCsvFileName(): string
    {
        return sprintf('list-subscribers-%s-segment-%s.csv', (string)$this->list->list_uid, (string)$this->segment_uid);
    }


    /**
     * @return ListSegment|null
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
     * @param mixed $subscriber
     *
     * @return bool
     * @throws CDbException
     */
    public function hasSubscriber($subscriber): bool
    {
        if ($subscriber instanceof ListSubscriber) {
            $subscriberId = (int)$subscriber->subscriber_id;
        } else {
            $subscriberId = (int)$subscriber;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.subscriber_id', (int)$subscriberId);

        return $this->countSubscribers($criteria) > 0;
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete(): bool
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
     * @return string
     */
    public function getSubscribersCountDisplay(): string
    {
        $cacheKey = sha1(sprintf(self::SUBSCRIBERS_COUNTER_KEY_PATTERN, $this->list_id, $this->segment_id));
        $count    = cache()->get($cacheKey);
        if ($count == false) {
            return $this->t('Pending counting');
        }
        if ($count == 0) {
            return '0';
        }
        return sprintf('~%s', numberFormatter()->formatDecimal($count));
    }

    /**
     * @param array $params
     *
     * @return CDbCriteria
     * @throws CDbException
     */
    protected function _createCountFindSubscribersCriteria(array $params = []): CDbCriteria
    {
        $segmentConditions = ListSegmentCondition::model()->findAllByAttributes([
            'segment_id' => (int)$this->segment_id,
        ]);

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', $this->list_id);

        if (empty($params['status']) || !is_array($params['status'])) {
            $criteria->compare('t.status', ListSubscriber::STATUS_CONFIRMED);
        } else {
            $criteria->addInCondition('t.status', $params['status']);
        }

        $criteria->group = 't.subscriber_id';
        $criteria->order = 't.subscriber_id ASC';

        $fieldConditions = [];
        foreach ($segmentConditions as $segmentCondition) {
            if (!isset($fieldConditions[$segmentCondition->field_id])) {
                $fieldConditions[$segmentCondition->field_id] = [];
            }
            $fieldConditions[$segmentCondition->field_id][] = $segmentCondition;
        }

        $subscriber = ListSubscriber::model();
        $md = $subscriber->getMetaData();
        foreach ($fieldConditions as $field_id => $conditions) {
            if ($md->hasRelation('fieldValues' . $field_id)) {
                continue;
            }
            $md->addRelation('fieldValues' . $field_id, [ListSubscriber::HAS_MANY, ListFieldValue::class, 'subscriber_id']);
        }
        $this->_fieldConditions = $fieldConditions;

        // since 1.9.12
        $campaignConditions = ListSegmentCampaignCondition::model()->findAllByAttributes([
            'segment_id' => $this->segment_id,
        ]);
        if (!empty($campaignConditions)) {
            foreach ($campaignConditions as $condition) {
                if ($condition->getIsOpenAction() && !$md->hasRelation($condition->getTimeComparisonAliasForDb())) {
                    $md->addRelation($condition->getTimeComparisonAliasForDb(), [ListSubscriber::HAS_MANY, 'CampaignTrackOpen', 'subscriber_id']);
                    continue;
                }
                if ($condition->getIsClickAction() && !$md->hasRelation($condition->getTimeComparisonAliasForDb())) {
                    $md->addRelation($condition->getTimeComparisonAliasForDb(), [ListSubscriber::HAS_MANY, 'CampaignTrackUrl', 'subscriber_id']);
                    continue;
                }
            }
        }
        //

        unset($segmentConditions, $fieldConditions);
        return $criteria;
    }

    /**
     * @param CDbCriteria $criteria
     */
    protected function _appendCountFindSubscribersCriteria(CDbCriteria $criteria): void
    {
        $fieldConditions = $this->_fieldConditions;

        $with                       = [];
        $params                     = [];
        $appendCriteriaCondition    = [];

        foreach ($fieldConditions as $field_id => $conditions) {
            $addWith         = true;
            $relationName    = 'fieldValues' . $field_id;
            $valueColumnName = '`fieldValues' . $field_id . '`.`value`';

            $field = ListField::model()->findByPk($field_id);

            // same table, avoid ugly join!
            if ($field->tag == 'EMAIL') {
                $addWith         = false;
                $relationName    = null;
                $valueColumnName = 't.email';
            }

            if ($addWith) {
                $with[$relationName] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'LEFT JOIN',
                ];
            }

            $conditionString = '1 = 1 AND (%s)';
            if ($addWith) {
                $conditionString = '(`fieldValues' . $field_id . '`.`field_id` = :field_id' . $field_id . ' AND (%s) )';
                $params[':field_id' . $field_id] = $field_id;
            }

            $injectCondition = [];

            // note: since 1.3.4.7, added the is_numeric() and is_float() checks and values casting if needed
            foreach ($conditions as $condition) {
                $index = '_' . $this->getUniqueIndexValue();
                $value = $condition->getParsedValue();

                if ($condition->operator->slug === ListSegmentOperator::IS) {
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

                if ($condition->operator->slug === ListSegmentOperator::IS_NOT) {
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

                if ($condition->operator->slug === ListSegmentOperator::CONTAINS) {
                    $injectCondition[] =  $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value . '%';
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::NOT_CONTAINS) {
                    $injectCondition[] =  $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value . '%';
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::STARTS_WITH) {
                    $injectCondition[] = $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = $value . '%';
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::NOT_STARTS_WITH) {
                    $injectCondition[] = $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = $value . '%';
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::ENDS_WITH) {
                    $injectCondition[] = $valueColumnName . ' LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value;
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::NOT_ENDS_WITH) {
                    $injectCondition[] = $valueColumnName . ' NOT LIKE :value' . $index;
                    $params[':value' . $index] = '%' . $value;
                    continue;
                }

                if ($condition->operator->slug === ListSegmentOperator::GREATER) {
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

                if ($condition->operator->slug == ListSegmentOperator::LESS) {
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
                if ($this->operator_match === ListSegment::OPERATOR_MATCH_ANY) {
                    $injectCondition = implode(' OR ', $injectCondition);
                } else {
                    $injectCondition = implode(' AND ', $injectCondition);
                }
                $appendCriteriaCondition[] = sprintf($conditionString, $injectCondition);
            }
        }

        // since 1.9.12
        $campaignConditions = ListSegmentCampaignCondition::model()->findAllByAttributes([
            'segment_id' => $this->segment_id,
        ]);
        foreach ($campaignConditions as $condition) {
            $injectCondition = [];
            $campaignIds     = [];
            if ($condition->getIsOpenAction() || $condition->getIsClickAction()) {
                if (!empty($condition->campaign_id)) {
                    $campaignIds = [(int)$condition->campaign_id];
                } else {
                    $campaignIds = array_keys($condition->getCampaignsList((int)$this->list_id));
                }
            }
            $campaignIds = array_filter($campaignIds);
            if (count($campaignIds) === 0) {
                $campaignIds = [0];
            }
            if ($condition->getIsOpenAction()) {
                $with[$condition->getTimeComparisonAliasForDb()] = [
                    'select'   => false,
                    'together' => true,
                    'joinType' => 'LEFT JOIN',
                ];
                $injectCondition[] = sprintf(
                    '(%s.campaign_id IN (' . implode(',', $campaignIds) . ') AND %s)',
                    $condition->getTimeComparisonAliasForDb(),
                    $condition->getTimeComparisonForDb()
                );
            }

            if ($condition->getIsClickAction()) {
                $with[$condition->getTimeComparisonAliasForDb()] = [
                    'select'   => false,
                    'together' => true,
                    'joinType' => 'LEFT JOIN',
                ];
                $injectCondition[] = sprintf(
                    '(%s.url_id IN (SELECT url_id FROM {{campaign_url}} WHERE campaign_id IN (' . implode(',', $campaignIds) . ')) AND %s)',
                    $condition->getTimeComparisonAliasForDb(),
                    $condition->getTimeComparisonForDb()
                );
            }

            if (!empty($injectCondition)) {
                if ($this->operator_match === ListSegment::OPERATOR_MATCH_ANY) {
                    $injectCondition = implode(' OR ', $injectCondition);
                } else {
                    $injectCondition = implode(' AND ', $injectCondition);
                }
                $appendCriteriaCondition[] = $injectCondition;
            }
        }
        //

        if (!empty($appendCriteriaCondition)) {
            $criteria->params = array_merge($criteria->params, $params);
            if ($this->operator_match === ListSegment::OPERATOR_MATCH_ANY) {
                $appendCondition = ' AND ' . '( ' . implode(' OR ', $appendCriteriaCondition) . ' )';
            } else {
                $appendCondition = ' AND ' . implode(' AND ', $appendCriteriaCondition);
            }

            $criteria->with = $with;
            $criteria->condition .= $appendCondition;
        } else {
            // add a condition to return nothing as a result
            $criteria->compare('t.subscriber_id', -1);
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

            // the campaigns
            CampaignCollection::findAllByAttributes([
                'segment_id' => $this->segment_id,
            ])->each(function (Campaign $campaign) {
                $campaign->saveStatus(Campaign::STATUS_PENDING_DELETE);
            });

            return false;
        }

        return parent::beforeDelete();
    }
}
