<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSplitTool
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 */

class ListSplitTool extends FormModel
{
    /**
     * @var int
     */
    public $customer_id  = 0;

    /**
     * @var int
     */
    public $list_id      = 0;

    /**
     * @var int
     */
    public $sublists     = 2;

    /**
     * @var int
     */
    public $count          = 0;

    /**
     * @var int
     */
    public $limit          = 500;

    /**
     * @var int
     */
    public $page           = 0;

    /**
     * @var int
     */
    public $per_list       = 0;

    /**
     * @var string
     */
    public $progress_text  = '';

    /**
     * @var int
     */
    public $percentage     = 0;

    /**
     * @var int
     */
    public $finished       = 0;

    /**
     * @var Lists
     */
    private $_list;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['list_id, sublists, limit', 'required'],
            ['list_id, sublists, limit, count, page, per_list, finished', 'numerical', 'integerOnly' => true],
            ['sublists', 'numerical', 'min' => 2, 'max' => 100],
            ['limit', 'numerical', 'max' => 1000],
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
            'list_id'   => t('lists', 'List'),
            'sublists'  => t('lists', 'Number of sublists'),
            'limit'     => t('lists', 'How many subscribers to move at once'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        return [];
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
        $criteria->compare('customer_id', $this->customer_id);
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

    /**
     * @return array
     */
    public function getLimitOptions(): array
    {
        return [
            100  => 100,
            300  => 300,
            500  => 500,
            1000 => 1000,
        ];
    }

    /**
     * @return Lists|null
     */
    public function getList(): ?Lists
    {
        if ($this->_list !== null) {
            return $this->_list;
        }

        if (empty($this->list_id) || empty($this->customer_id)) {
            return null;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$this->list_id);
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return $this->_list = Lists::model()->find($criteria);
    }
}
