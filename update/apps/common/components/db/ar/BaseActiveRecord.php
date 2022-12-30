<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BaseActiveRecord
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property mixed $onRules
 * @property mixed $onBeforeValidate
 * @property mixed $onAfterValidate
 * @property mixed $onAfterSave
 * @property mixed $onHtmlOptionsSetup
 * @property mixed $onAttributeLabels
 * @property mixed $onAttributeHelpTexts
 * @property bool $isNewRecord
 * @property array $attributes
 * @property string $status
 * @property AttributesShortErrorsBehavior $shortErrors
 * @property AttributeFieldDecoratorBehavior $fieldDecorator
 * @property PaginationOptionsBehavior $paginationOptions
 * @property ModelMetaDataBehavior $modelMetaData
 * @property StickySearchFiltersBehavior $stickySearchFilters
 * @property DateTimeFormatterBehavior $dateTimeFormatter
 */
class BaseActiveRecord extends CActiveRecord
{
    /**
     * Add the needed traits
     */
    use AddTranslationFunctionalityByCategoryTrait;

    /**
     * Flag for active status
     */
    const STATUS_ACTIVE = 'active';

    /**
     * Flag for inactive status
     */
    const STATUS_INACTIVE = 'inactive';

    /**
     * Flag for deleted status
     */
    const STATUS_DELETED = 'deleted';

    /**
     * Flag for bulk delete
     */
    const BULK_ACTION_DELETE = 'delete';

    /**
     * Flag for bulk copy
     */
    const BULK_ACTION_COPY = 'copy';

    /**
     * Flag for confirmation
     */
    const TEXT_YES = 'yes';

    /**
     * Flag for confirmation
     */
    const TEXT_NO = 'no';

    /**
     * @since 1.6.6
     * @var string
     */
    public $afterFindStatus = '';

    /**
     * @var bool
     */
    protected $validationHasBeenMade = false;

    /**
     * @var string
     */
    private $_modelName;

    /**
     * @var array
     */
    private static $_relatedCached = [];

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = new CList();

        /** @var CList $rules */
        $rules = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $rules);

        /** @var CList $rules */
        $rules = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $rules);

        $this->onRules(new CModelEvent($this, [
            'rules' => $rules,
        ]));

        return $rules->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onRules(CModelEvent $event)
    {
        $this->raiseEvent('onRules', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        $behaviors = CMap::mergeArray(parent::behaviors(), [
            'shortErrors' => [
                'class' => 'common.components.behaviors.AttributesShortErrorsBehavior',
            ],
            'fieldDecorator' => [
                'class' => 'common.components.behaviors.AttributeFieldDecoratorBehavior',
            ],
            'modelMetaData' => [
                'class' => 'common.components.db.behaviors.ModelMetaDataBehavior',
            ],
            'paginationOptions' => [
                'class' => 'common.components.behaviors.PaginationOptionsBehavior',
            ],
            'stickySearchFilters' => [
                'class' => 'common.components.behaviors.StickySearchFiltersBehavior',
            ],
        ]);

        if ($this->hasAttribute('date_added') || $this->hasAttribute('last_updated')) {
            $behaviors['CTimestampBehavior'] = [
                'class'           => 'zii.behaviors.CTimestampBehavior',
                'createAttribute' => null,
                'updateAttribute' => null,
            ];

            if ($this->hasAttribute('date_added')) {
                $behaviors['CTimestampBehavior']['createAttribute'] = 'date_added';
            }

            if ($this->hasAttribute('last_updated')) {
                $behaviors['CTimestampBehavior']['updateAttribute'] = 'last_updated';
                $behaviors['CTimestampBehavior']['setUpdateOnCreate'] = true;
            }
        }

        $behaviors['dateTimeFormatter'] = [
                'class'                 => 'common.components.db.behaviors.DateTimeFormatterBehavior',
                'dateAddedAttribute'    => 'date_added',
                'lastUpdatedAttribute'  => 'last_updated',
                'timeZone'              => null,
        ];

        $behaviors  = new CMap($behaviors);

        /** @var CMap $behaviors */
        $behaviors  = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $behaviors);

        /** @var CMap $behaviors */
        $behaviors  = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $behaviors);

        $this->onBehaviors(new CModelEvent($this, [
            'behaviors' => $behaviors,
        ]));

        return $behaviors->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onBehaviors(CModelEvent $event)
    {
        $this->raiseEvent('onBehaviors', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = new CMap([
            'status'        => t('app', 'Status'),
            'date_added'    => t('app', 'Date added'),
            'last_updated'  => t('app', 'Last updated'),
        ]);

        /** @var CMap $labels */
        $labels = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $labels);

        /** @var CMap $labels */
        $labels = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $labels);

        $this->onAttributeLabels(new CModelEvent($this, [
            'labels' => $labels,
        ]));

        return $labels->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributeLabels(CModelEvent $event)
    {
        $this->raiseEvent('onAttributeLabels', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        /** @var CMap $relations */
        $relations = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), new CMap());

        $this->onRelations(new CModelEvent($this, [
            'relations' => $relations,
        ]));

        return $relations->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onRelations(CModelEvent $event)
    {
        $this->raiseEvent('onRelations', $event);
    }

    /**
     * @return array
     * @throws CException
     */
    public function scopes()
    {
        $scopes = new CMap([
            'active' => [
                'condition' => $this->getTableAlias(false, false) . '`status` = :st',
                'params' => [':st' => self::STATUS_ACTIVE],
            ],
            'inactive' => [
                'condition' => $this->getTableAlias(false, false) . '`status` = :st',
                'params' => [':st' => self::STATUS_INACTIVE],
            ],
            'deleted' => [
                'condition' => $this->getTableAlias(false, false) . '`status` = :st',
                'params' => [':st' => self::STATUS_DELETED],
            ],
        ]);

        /** @var CMap $scopes */
        $scopes = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $scopes);

        /** @var CMap $scopes */
        $scopes = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $scopes);

        $this->onScopes(new CModelEvent($this, [
            'scopes' => $scopes,
        ]));

        return $scopes->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onScopes(CModelEvent $event)
    {
        $this->raiseEvent('onScopes', $event);
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts  = new CMap();

        /** @var CMap $texts */
        $texts  = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $texts);

        /** @var CMap $texts */
        $texts  = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $texts);

        $this->onAttributeHelpTexts(new CModelEvent($this, [
            'texts' => $texts,
        ]));

        return $texts->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributeHelpTexts(CModelEvent $event)
    {
        $this->raiseEvent('onAttributeHelpTexts', $event);
    }

    /**
     * @return mixed
     * @throws CException
     */
    public function attributePlaceholders()
    {
        $placeholders = new CMap();

        /** @var CMap $placeholders */
        $placeholders = hooks()->applyFilters($this->buildHookName(['class' => false, 'suffix' => strtolower(__FUNCTION__)]), $placeholders);

        /** @var CMap $placeholders */
        $placeholders = hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $placeholders);

        $this->onAttributePlaceholders(new CModelEvent($this, [
            'placeholders' => $placeholders,
        ]));

        return $placeholders->toArray();
    }

    /**
     * @param CModelEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onAttributePlaceholders(CModelEvent $event)
    {
        $this->raiseEvent('onAttributePlaceholders', $event);
    }

    /**
     * @return string
     */
    public function getModelName(): string
    {
        if ($this->_modelName === null) {
            $this->_modelName = get_class($this);
        }
        return $this->_modelName;
    }

    /**
     * @param string $status
     *
     * @return bool
     * @throws Exception
     */
    public function saveStatus(string $status = ''): bool
    {
        if ($this->getIsNewRecord() || !$this->hasAttribute('status')) {
            return false;
        }

        if ($status && $status === (string)$this->status) {
            return true;
        }

        if ($status) {
            $this->status = $status;
        }

        $attributes = [
            'status'        => $this->status,
            'last_updated'  => MW_DATETIME_NOW,
        ];

        // 1.7.9
        hooks()->doAction($this->buildHookName(['suffix' => 'before_savestatus']), $this);
        //

        $result = (bool)$this->saveAttributes($attributes);

        // 1.7.9
        hooks()->doAction($this->buildHookName(['suffix' => 'after_savestatus']), $this, $result);
        //

        return $result;
    }

    /**
     * @return array
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_ACTIVE     => t('app', 'Active'),
            self::STATUS_INACTIVE   => t('app', 'Inactive'),
            // self::STATUS_DELETED    => t('app', 'Deleted'),
        ];
    }

    /**
     * @return array
     */
    public function getBulkActionsList(): array
    {
        return [
            self::BULK_ACTION_DELETE => t('app', 'Delete'),
        ];
    }

    /**
     * @param string $status
     * @return bool
     */
    public function getStatusIs(string $status): bool
    {
        return $this->hasAttribute('status') && (string)$this->status === $status;
    }

    /**
     * @param string $status
     * @return string
     */
    public function getStatusName(string $status = ''): string
    {
        if (!$status && $this->hasAttribute('status')) {
            $status = $this->status;
        }
        if (!$status) {
            return '';
        }
        $list = $this->getStatusesList();
        return $list[$status] ?? t('app', ucfirst((string)preg_replace('/[^a-z]/', ' ', strtolower((string)$status))));
    }

    /**
     * @return array
     */
    public function getYesNoOptions(): array
    {
        return [
            self::TEXT_YES  => ucfirst(t('app', self::TEXT_YES)),
            self::TEXT_NO   => ucfirst(t('app', self::TEXT_NO)),
        ];
    }

    /**
     * @return array
     */
    public function getComparisonSignsList(): array
    {
        return [
            '='  => '=',
            '>'  => '>',
            '>=' => '>=',
            '<'  => '<',
            '<=' => '<=',
            '<>' => '<>',
        ];
    }

    /**
     * @since 1.3.6.2
     * @return array
     */
    public function getSortOrderList(): array
    {
        return (array)array_combine(range(-100, 100), range(-100, 100));
    }

    /**
     * @param string $name
     * @param bool $refresh
     * @param array $params
     *
     * @return mixed
     * @throws CDbException
     */
    public function getRelated($name, $refresh=false, $params=[])
    {
        $cacheKey       = '';
        $relationKey    = '';
        $cache          = false;

        /** @var CActiveRecordMetaData $md */
        $md = $this->getMetaData();

        /** @var CActiveRelation|null $rel */
        $rel = isset($md->relations[$name]) && is_object($md->relations[$name]) ? $md->relations[$name] : null;

        if (!empty($rel) && is_string($rel->foreignKey) && $this->hasAttribute($rel->foreignKey)) {
            $relationKey = $rel->foreignKey;
            $cacheKey    = $name . '_' . $rel->className . '_' . get_class($this);
            $relationKey = $this->$relationKey;
            $cache       = true;
        }

        if (($refresh || !empty($params)) && $cache && (isset(self::$_relatedCached[$cacheKey][$relationKey]) || array_key_exists($relationKey, self::$_relatedCached[$cacheKey]))) {
            unset(self::$_relatedCached[$cacheKey][$relationKey]);
        }

        if ($cache && !isset(self::$_relatedCached[$cacheKey])) {
            self::$_relatedCached[$cacheKey] = [];
        }

        $related = -1;
        if ($cache && (isset(self::$_relatedCached[$cacheKey][$relationKey]) || array_key_exists($relationKey, self::$_relatedCached[$cacheKey]))) {
            $related = self::$_relatedCached[$cacheKey][$relationKey];
        }

        if ($related === -1) {
            $related = parent::getRelated($name, $refresh, $params);
            if ($cache) {
                self::$_relatedCached[$cacheKey][$relationKey] =&$related;
            }
        }

        return $related;
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        parent::afterFind();

        if ($this->hasAttribute('status') && !empty($this->status)) {
            $this->afterFindStatus = $this->status;
        }
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->validationHasBeenMade = true;

        return true;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        hooks()->doAction($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $this);
    }

    /**
     * @since 1.9.19
     *
     * @return bool
     * @throws Exception
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        return (bool)hooks()->applyFilters($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), true, $this);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function afterSave()
    {
        parent::afterSave();

        hooks()->doAction($this->buildHookName(['suffix' => strtolower(__FUNCTION__)]), $this);
    }

    /**
     * @since 1.7.9
     *
     * @param array $options
     *
     * @return string
     * @throws Exception
     */
    final protected function buildHookName(array $options)
    {
        $options = CMap::mergeArray([
            'suffix' => '',
            'app'    => true,
            'class'  => true,
        ], $options);

        if (empty($options['suffix'])) {
            throw new Exception(t('app', 'Please provide a suffix when building the hook name!'));
        }

        $hookParts = [];

        if ($options['app']) {
            $hookParts[] = apps()->getCurrentAppName();
        }

        $hookParts[] = 'model';

        if ($options['class']) {
            $hookParts[] = strtolower(get_class($this));
        }

        $hookParts[] = $options['suffix'];

        return implode('_', array_filter($hookParts));
    }
}
