<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListsSyncTool
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class ListsSyncTool extends FormModel
{
    /**
     * Actions list
     */
    const MISSING_SUBSCRIBER_ACTION_NONE              = '';
    const MISSING_SUBSCRIBER_ACTION_CREATE_SECONDARY  = 'create-secondary';

    /**
     * Actions list
     */
    const DISTINCT_STATUS_ACTION_NONE               = '';
    const DISTINCT_STATUS_ACTION_UPDATE_PRIMARY     = 'update-primary';
    const DISTINCT_STATUS_ACTION_UPDATE_SECONDARY   = 'update-secondary';
    const DISTINCT_STATUS_ACTION_DELETE_SECONDARY   = 'delete-secondary';

    /**
     * Actions list
     */
    const DUPLICATE_SUBSCRIBER_ACTION_NONE              = '';
    const DUPLICATE_SUBSCRIBER_ACTION_DELETE_SECONDARY  = 'delete-secondary';

    /**
     * @var int
     */
    public $customer_id = 0;

    /**
     * @var int
     */
    public $primary_list_id = 0;

    /**
     * @var int
     */
    public $secondary_list_id = 0;

    /**
     * @var string
     */
    public $missing_subscribers_action = '';

    /**
     * @var string
     */
    public $duplicate_subscribers_action = '';

    /**
     * @var string
     */
    public $distinct_status_action = '';

    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var int
     */
    public $limit = 100;

    /**
     * @var int
     */
    public $offset = 0;

    /**
     * @var string
     */
    public $progress_text = '';

    /**
     * @var int
     */
    public $processed_total = 0;

    /**
     * @var int
     */
    public $processed_success = 0;

    /**
     * @var int
     */
    public $processed_error = 0;

    /**
     * @var int
     */
    public $percentage = 0;

    /**
     * @var int
     */
    public $finished = 0;

    /**
     * @var Lists
     */
    protected $_primaryList;

    /**
     * @var Lists
     */
    protected $_secondaryList;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['primary_list_id, secondary_list_id', 'required'],
            ['primary_list_id, secondary_list_id', 'numerical', 'integerOnly' => true],
            ['missing_subscribers_action', 'in', 'range' => array_keys($this->getMissingSubscribersActions())],
            ['distinct_status_action', 'in', 'range' => array_keys($this->getDistinctStatusActions())],
            ['duplicate_subscribers_action', 'in', 'range' => array_keys($this->getDuplicateSubscribersActions())],

            ['count, limit, offset, processed_total, processed_success, processed_error, finished', 'numerical', 'integerOnly' => true],
            ['percentage', 'numerical'],
            ['progress_text', 'safe'],

            ['customer_id', 'unsafe'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'primary_list_id'               => t('lists', 'Primary list'),
            'secondary_list_id'             => t('lists', 'Secondary list'),
            'missing_subscribers_action'    => t('lists', 'Action on missing subscribers'),
            'distinct_status_action'        => t('lists', 'Action when distinct subscriber status'),
            'duplicate_subscribers_action'  => t('lists', 'Action on duplicate subscribers'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        return [
            'primary_list_id'               => t('lists', 'Primary list'),
            'secondary_list_id'             => t('lists', 'Secondary list'),
            'missing_subscribers_action'    => t('lists', 'What actions to take when a subscriber is found in the primary list but not in the secondary list'),
            'distinct_status_action'        => t('lists', 'What actions to take when same subscriber from primary list has a distinct status in the secondary list'),
            'duplicate_subscribers_action'  => t('lists', 'What actions to take when same subscriber is found in both lists'),
        ];
    }

    /**
     * @return array
     */
    public function getMissingSubscribersActions(): array
    {
        return [
            self::MISSING_SUBSCRIBER_ACTION_NONE              => t('lists', 'Do nothing'),
            self::MISSING_SUBSCRIBER_ACTION_CREATE_SECONDARY  => t('lists', 'Create subscriber in secondary list'),
        ];
    }

    /**
     * @return array
     */
    public function getDistinctStatusActions(): array
    {
        return [
            self::DISTINCT_STATUS_ACTION_NONE               => t('lists', 'Do nothing'),
            self::DISTINCT_STATUS_ACTION_UPDATE_PRIMARY     => t('lists', 'Update subscriber in primary list'),
            self::DISTINCT_STATUS_ACTION_UPDATE_SECONDARY   => t('lists', 'Update subscriber in secondary list'),
            self::DISTINCT_STATUS_ACTION_DELETE_SECONDARY   => t('lists', 'Delete subscriber from secondary list'),
        ];
    }

    /**
     * @return array
     */
    public function getDuplicateSubscribersActions(): array
    {
        return [
            self::DUPLICATE_SUBSCRIBER_ACTION_NONE              => t('lists', 'Do nothing'),
            self::DUPLICATE_SUBSCRIBER_ACTION_DELETE_SECONDARY  => t('lists', 'Delete subscriber from secondary list'),
        ];
    }

    /**
     * @return Lists|null
     */
    public function getPrimaryList(): ?Lists
    {
        if ($this->_primaryList !== null) {
            return $this->_primaryList;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$this->primary_list_id);
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return $this->_primaryList = Lists::model()->find($criteria);
    }

    /**
     * @return Lists|null
     */
    public function getSecondaryList(): ?Lists
    {
        if ($this->_secondaryList !== null) {
            return $this->_secondaryList;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$this->secondary_list_id);
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return $this->_secondaryList = Lists::model()->find($criteria);
    }

    /**
     * @return array
     */
    public function getAsDropDownOptionsByCustomerId(): array
    {
        $this->customer_id = (int)$this->customer_id;

        static $options = [];
        if (isset($options[$this->customer_id])) {
            return $options[$this->customer_id];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id, name';
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $criteria->order = 'name ASC';

        return $options[$this->customer_id] = ListsCollection::findAll($criteria)->mapWithKeys(function (Lists $list) {
            return [$list->list_id => $list->name];
        })->all();
    }

    /**
     * @return array
     */
    public function getFormattedAttributes(): array
    {
        $out = [];
        foreach ($this->getAttributes() as $key => $value) {
            $out[sprintf('%s[%s]', $this->getModelName(), $key)] = $value;
        }
        return $out;
    }
}
