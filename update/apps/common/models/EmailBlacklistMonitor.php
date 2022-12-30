<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistMonitor
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.9
 */

/**
 * This is the model class for table "email_blacklist_monitor".
 *
 * The followings are the available columns in table 'email_blacklist_monitor':
 * @property integer $monitor_id
 * @property string $name
 * @property string $email_condition
 * @property string $email
 * @property string $reason_condition
 * @property string $reason
 * @property string $condition_operator
 * @property string $notifications_to
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class EmailBlacklistMonitor extends ActiveRecord
{
    /**
     * Condition flags
     */
    const CONDITION_EQUALS = 'equals';
    const CONDITION_CONTAINS = 'contains';
    const CONDITION_STARTS_WITH = 'starts with';
    const CONDITION_ENDS_WITH = 'ends with';

    /**
     * Operator flags
     */
    const OPERATOR_AND = 'and';
    const OPERATOR_OR = 'or';

    /**
     * @var CUploadedFile $file - the uploaded file for import
     */
    public $file;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{email_blacklist_monitor}}';
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
            ['name, condition_operator, status', 'required', 'on' => 'insert, update'],

            ['email_condition, reason_condition', 'in', 'range' => array_keys($this->getConditionsList())],
            ['name, email, reason, notifications_to', 'length', 'max' => 255],
            ['condition_operator', 'in', 'range' => array_keys($this->getConditionOperatorsList())],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            ['file', 'required', 'on' => 'import'],
            ['file', 'file', 'types' => ['csv'], 'mimeTypes' => $mimes, 'maxSize' => 512000000, 'allowEmpty' => true],

            // search
            ['name, email_condition, reason_condition, email, reason, notifications_to, condition_operator, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'monitor_id'         => t('email_blacklist', 'Monitor'),
            'name'               => t('email_blacklist', 'Name'),
            'email_condition'    => t('email_blacklist', 'Email condition'),
            'email'              => t('email_blacklist', 'Email match'),
            'reason_condition'   => t('email_blacklist', 'Reason condition'),
            'reason'             => t('email_blacklist', 'Reason match'),
            'condition_operator' => t('email_blacklist', 'Condition operator'),
            'notifications_to'   => t('email_blacklist', 'Notifications to'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'               => t('email_blacklist', 'Name your monitor for easier identification'),
            'email_condition'    => t('email_blacklist', 'How to match against the blacklisted email address'),
            'email'              => t('email_blacklist', 'The text to match against the email address'),
            'reason_condition'   => t('email_blacklist', 'How to match against the blacklisted reason'),
            'reason'             => t('email_blacklist', 'The text to match against the blacklist reason. Use the [EMPTY] tag to match empty content'),
            'condition_operator' => t('email_blacklist', 'What operator to use between the conditions'),
            'notifications_to'   => t('email_blacklist', 'Where to send notifications when the conditions are met. Separate multiple email addresses with a comma'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'email'              => 'yahoo.com',
            'reason'             => t('email_blacklist', 'Greylisted'),
            'notifications_to'   => 'a@domain.com, b@domain.com, c@domain.com',
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
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

        $criteria->compare('name', $this->name, true);
        $criteria->compare('monitor_id', $this->monitor_id);
        $criteria->compare('email_condition', $this->email_condition);
        $criteria->compare('email', $this->email, true);
        $criteria->compare('reason_condition', $this->reason_condition);
        $criteria->compare('reason', $this->reason, true);
        $criteria->compare('condition_operator', $this->condition_operator);
        $criteria->compare('notifications_to', $this->notifications_to, true);
        $criteria->compare('status', $this->status);

        $criteria->order = 'name ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'monitor_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return EmailBlacklistMonitor the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var EmailBlacklistMonitor $parent */
        $parent = parent::model($className);

        return $parent;
    }

    /**
     * @return array
     */
    public function getConditionsList(): array
    {
        return [
            self::CONDITION_EQUALS      => t('email_blacklist', ucfirst(self::CONDITION_EQUALS)),
            self::CONDITION_CONTAINS    => t('email_blacklist', ucfirst(self::CONDITION_CONTAINS)),
            self::CONDITION_STARTS_WITH => t('email_blacklist', ucfirst(self::CONDITION_STARTS_WITH)),
            self::CONDITION_ENDS_WITH   => t('email_blacklist', ucfirst(self::CONDITION_ENDS_WITH)),
        ];
    }

    /**
     * @return array
     */
    public function getConditionOperatorsList(): array
    {
        return [
            self::OPERATOR_AND => t('email_blacklist', ucfirst(self::OPERATOR_AND)),
            self::OPERATOR_OR  => t('email_blacklist', ucfirst(self::OPERATOR_OR)),
        ];
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function afterValidate()
    {
        if (!empty($this->notifications_to)) {
            $notificationsTo = CommonHelper::getArrayFromString((string)$this->notifications_to);
            foreach ($notificationsTo as $index => $email) {
                if (!FilterVarHelper::email($email)) {
                    unset($notificationsTo[$index]);
                }
            }
            $notificationsTo = array_values($notificationsTo); // reset
            $this->notifications_to = CommonHelper::getStringFromArray($notificationsTo, ',');
        }

        if (in_array($this->getScenario(), ['insert', 'update'])) {
            if (empty($this->email) && empty($this->reason)) {
                $this->addError('email', t('email_blacklist', 'Please specify at least the email and/or the reason!'));
                $this->addError('reason', t('email_blacklist', 'Please specify at least the email and/or the reason!'));
            }

            if (!empty($this->email) && empty($this->email_condition)) {
                $this->addError('email_condition', t('email_blacklist', 'Please specify the condition!'));
            }

            if (!empty($this->reason) && empty($this->reason_condition)) {
                $this->addError('reason_condition', t('email_blacklist', 'Please specify the condition!'));
            }
        }

        parent::afterValidate();
    }
}
