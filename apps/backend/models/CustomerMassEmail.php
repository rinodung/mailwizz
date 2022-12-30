<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UserLogin
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CustomerMassEmail extends FormModel
{
    /**
     * Storage alias
     */
    const STORAGE_ALIAS = 'common.runtime.customer-mass-email';

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    public $groups = [];

    /**
     * @var string
     */
    public $message_id;

    /**
     * @var int
     */
    public $batch_size = 300;

    /**
     * @var int
     */
    public $page = 1;

    /**
     * @var int
     */
    public $total = 0;

    /**
     * @var array
     */
    public $customers = [];

    /**
     * @var int
     */
    public $processed = 0;

    /**
     * @var int
     */
    public $percentage = 0;

    /**
     * @var string
     */
    public $progress_text;

    /**
     * @var bool
     */
    public $finished = false;

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['subject, message', 'required'],
            ['page, total, processed, percentage, batch_size', 'numerical', 'integerOnly' => true],
            ['groups, message_id', 'safe'],
        ];

        return CMap::mergeArray(parent::rules(), $rules);
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'subject'    => t('customers', 'Subject'),
            'message'    => t('customers', 'Message'),
            'groups'     => t('customers', 'Groups'),
            'batch_size' => t('customers', 'Batch size'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function getGroupsList(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }
        $options = [];
        $groups  = CustomerGroup::model()->findAll();
        foreach ($groups as $group) {
            $count = Customer::model()->countByAttributes(['group_id' => $group->group_id, 'status' => Customer::STATUS_ACTIVE]);
            if ($count == 0) {
                continue;
            }
            $options[$group->group_id] = t('customers', '{group} ({count} customers)', ['{group}' => $group->name, '{count}' => $count]);
        }
        return $options;
    }

    /**
     * @return void
     */
    public function loadCustomers(): void
    {
        $criteria = new CDbCriteria();
        $criteria->compare('status', Customer::STATUS_ACTIVE);
        if (!empty($this->groups) && is_array($this->groups)) {
            $this->groups = array_map('intval', array_values($this->groups));
            $criteria->addInCondition('group_id', $this->groups);
        }
        $this->total = Customer::model()->count($criteria);
        if (empty($this->total)) {
            return;
        }
        $criteria->limit  = $this->batch_size;
        $criteria->offset = ($this->page - 1) * $this->batch_size;
        $this->customers  = Customer::model()->findAll($criteria);
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
    public function getBatchSizes(): array
    {
        return [
            100 => 100,
            300 => 300,
            500 => 500,
            1000 => 1000,
        ];
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        if ($this->hasErrors()) {
            return;
        }

        $storage = (string)Yii::getPathOfAlias(self::STORAGE_ALIAS);
        if ((!file_exists($storage) || !is_dir($storage)) && !mkdir($storage, 0777)) {
            $this->addError('message', t('customers', 'Unable to create the storage directory {dir}', ['{dir}' => $storage]));
            return;
        }

        $this->message_id = StringHelper::random(20);
        if (!file_put_contents($storage . '/' . $this->message_id, $this->message)) {
            $this->addError('message', t('customers', 'Unable to write in the storage directory {dir}', ['{dir}' => $storage]));
            return;
        }
        $this->message = '';
    }
}
