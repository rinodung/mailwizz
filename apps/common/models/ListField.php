<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListField
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_field".
 *
 * The followings are the available columns in table 'list_field':
 * @property integer|null $field_id
 * @property integer $type_id
 * @property integer $list_id
 * @property string $label
 * @property string $tag
 * @property string $default_value
 * @property string $help_text
 * @property string $description
 * @property string $required
 * @property string $visibility
 * @property integer $sort_order
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignOpenActionListField[] $campaignOpenActionListFields
 * @property CampaignSentActionListField[] $campaignSentActionListFields
 * @property CampaignTemplateUrlActionListField[] $campaignTemplateUrlActionListFields
 * @property Lists $list
 * @property ListFieldType $type
 * @property ListFieldOption[] $options
 * @property ListFieldOption $option
 * @property ListFieldValue[] $values
 * @property ListFieldValue[] $value
 * @property ListSegmentCondition[] $segmentConditions
 */
class ListField extends ActiveRecord
{
    /**
     * Visibility flags
     */
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_HIDDEN = 'hidden';
    const VISIBILITY_NONE = 'none';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_field}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['type_id, label, tag, required, visibility, sort_order', 'required'],

            ['type_id', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['type_id', 'exist', 'className' => ListFieldType::class],
            ['label, help_text, description, default_value', 'length', 'min' => 1, 'max' => 255],
            ['tag', 'length', 'min' => 1, 'max' => 50],
            ['tag', 'match', 'pattern' => '#^(([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}]+)([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9\_]+)?([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9]+)?)$#u'],
            ['tag', '_checkIfAttributeUniqueInList'],
            ['tag', '_checkIfTagReserved'],
            ['required', 'in', 'range' => array_keys($this->getRequiredOptionsArray())],
            ['visibility', 'in', 'range' => array_keys($this->getVisibilityOptionsArray())],
            ['sort_order', 'numerical', 'min' => -100, 'max' => 100],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaignOpenActionListFields'          => [self::HAS_MANY, CampaignOpenActionListField::class, 'field_id'],
            'campaignSentActionListFields'          => [self::HAS_MANY, CampaignSentActionListField::class, 'field_id'],
            'campaignTemplateUrlActionListFields'   => [self::HAS_MANY, CampaignTemplateUrlActionListField::class, 'field_id'],
            'list'                                  => [self::BELONGS_TO, Lists::class, 'list_id'],
            'type'                                  => [self::BELONGS_TO, ListFieldType::class, 'type_id'],
            'options'                               => [self::HAS_MANY, ListFieldOption::class, 'field_id'],
            'option'                                => [self::HAS_ONE, ListFieldOption::class, 'field_id'],
            'values'                                => [self::HAS_MANY, ListFieldValue::class, 'field_id'],
            'value'                                 => [self::HAS_ONE, ListFieldValue::class, 'field_id'],
            'segmentConditions'                     => [self::HAS_MANY, ListSegmentCondition::class, 'field_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'field_id'      => t('list_fields', 'Field'),
            'type_id'       => t('list_fields', 'Type'),
            'list_id'       => t('list_fields', 'List'),
            'label'         => t('list_fields', 'Label'),
            'tag'           => t('list_fields', 'Tag'),
            'default_value' => t('list_fields', 'Default value'),
            'help_text'     => t('list_fields', 'Help text'),
            'description'   => t('list_fields', 'Description'),
            'required'      => t('list_fields', 'Required'),
            'visibility'    => t('list_fields', 'Visibility'),
            'sort_order'    => t('list_fields', 'Sort order'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListField the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListField $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _checkIfAttributeUniqueInList(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$this->list_id);
        $criteria->compare($attribute, $this->$attribute);
        $criteria->addNotInCondition('field_id', [(int)$this->field_id]);

        $exists = self::model()->find($criteria);

        if (!empty($exists)) {
            $this->addError($attribute, t('list_fields', 'The {attribute} attribute must be unique in the mail list!', [
                '{attribute}' => $attribute,
            ]));
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _checkIfTagReserved(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $exists = TagRegistry::model()->findByAttributes(['tag' => '[' . $this->$attribute . ']']);
        if (!empty($exists)) {
            $this->addError($attribute, t('list_fields', '"{tagName}" is reserved!', [
                '{tagName}' => html_encode($this->$attribute),
            ]));
        }

        // since 1.3.5.9
        if (strpos($this->$attribute, CustomerCampaignTag::getTagPrefix()) === 0) {
            $this->addError($attribute, t('list_fields', '"{tagName}" is reserved!', [
                '{tagName}' => html_encode($this->$attribute),
            ]));
        }
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $tags  = implode(', ', array_map(['CHtml', 'encode'], array_keys(self::getDefaultValueTags())));
        $texts = [
            'label'         => t('list_fields', 'This is what your subscribers will see above the input field.'),
            'tag'           => t('list_fields', 'The tag must be unique amoung the list tags. It must start with a letter, end with a letter or number and contain only alpha-numeric chars and underscores, all uppercased. The tag can be used in your templates like: [TAGNAME]'),
            'default_value' => t('list_fields', 'In case this field is not required and you need a default value for it. Following tags are recognized: {tags}', ['{tags}' => $tags]),
            'help_text'     => t('list_fields', 'If you need to describe this field to your subscribers.'),
            'description'   => t('list_fields', 'Additional description for this field to show to your subscribers.'),
            'required'      => t('list_fields', 'Whether this field must be filled in in order to submit the subscription form.'),
            'visibility'    => t('list_fields', 'Hidden fields are rendered in the form but hidden, while None fields are simply not rendered in the form at all.'),
            'sort_order'    => t('list_fields', 'Decide the order of the fields shown in the form.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getRequiredOptionsArray(): array
    {
        return [
            self::TEXT_YES   => t('app', 'Yes'),
            self::TEXT_NO    => t('app', 'No'),
        ];
    }

    /**
     * @return array
     */
    public function getVisibilityOptionsArray(): array
    {
        return [
            self::VISIBILITY_VISIBLE    => t('app', 'Visible'),
            self::VISIBILITY_HIDDEN     => t('app', 'Hidden'),
            self::VISIBILITY_NONE       => t('app', 'None'),
        ];
    }

    /**
     * @return array
     */
    public function getSortOrderOptionsArray(): array
    {
        static $_opts = [];
        if (!empty($_opts)) {
            return $_opts;
        }

        for ($i = -100; $i <= 100; ++$i) {
            $_opts[$i] = $i;
        }

        return $_opts;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsVisible(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_VISIBLE;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsHidden(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_HIDDEN;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsNone(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_NONE;
    }

    /**
     * @param int $listId
     *
     * @return array
     */
    public static function getAllByListId(int $listId): array
    {
        static $fields = [];
        if (!isset($fields[$listId])) {
            $fields[$listId] = [];
            $criteria = new CDbCriteria();
            $criteria->select = 't.field_id, t.tag';
            $criteria->compare('t.list_id', $listId);
            $models = self::model()->findAll($criteria);
            foreach ($models as $model) {
                $fields[$listId][] = $model->getAttributes(['field_id', 'tag']);
            }
        }
        return $fields[$listId];
    }

    /**
     * @param ListSubscriber|null $subscriber
     *
     * @return array
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function getDefaultValueTags(?ListSubscriber $subscriber = null): array
    {
        $ip = $userAgent = '';

        if (!is_cli()) {
            $ip        = (string)request()->getUserHostAddress();
            $userAgent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
        }

        if (empty($ip) && !empty($subscriber) && !empty($subscriber->ip_address)) {
            $ip        = $subscriber->ip_address;
            $userAgent = !empty($subscriber->user_agent) ? $subscriber->user_agent : '';
        }

        $geoCountry = $geoCity = $geoState = '';
        if (!empty($ip) && ($location = IpLocation::findByIp($ip))) {
            $geoCountry = $location->country_name;
            $geoCity    = $location->city_name;
            $geoState   = $location->zone_name;
        }

        $lastOpenDatetime = $lastClickDatetime = $lastSendDatetime = '';
        $lastOpenDate = $lastClickDate = $lastSendDate = '';

        // 1.9.33
        $dateAdded = $dateTimeAdded = '';

        if (!empty($subscriber)) {
            $lastOpenDatetime  = $subscriber->getLastOpenDate();
            $lastClickDatetime = $subscriber->getLastClickDate();
            $lastSendDatetime  = $subscriber->getLastSendDate();

            $lastOpenDate  = $subscriber->getLastOpenDate('Y-m-d');
            $lastClickDate = $subscriber->getLastClickDate('Y-m-d');
            $lastSendDate  = $subscriber->getLastSendDate('Y-m-d');

            // 1.9.33
            $dateAdded     = (string)date('Y-m-d', !empty($subscriber->date_added) && is_string($subscriber->date_added) ? (int)strtotime((string)$subscriber->date_added) : time());
            $dateTimeAdded = (string)date('Y-m-d H:i:s', !empty($subscriber->date_added) && is_string($subscriber->date_added) ? (int)strtotime((string)$subscriber->date_added) : time());
        }

        $tags = [
            '[DATETIME]'                       => date('Y-m-d H:i:s'),
            '[DATE]'                           => date('Y-m-d'),
            '[SUBSCRIBER_IP]'                  => $ip,
            '[SUBSCRIBER_USER_AGENT]'          => $userAgent,
            '[SUBSCRIBER_GEO_COUNTRY]'         => $geoCountry,
            '[SUBSCRIBER_GEO_STATE]'           => $geoState,
            '[SUBSCRIBER_GEO_CITY]'            => $geoCity,
            '[SUBSCRIBER_DATE_ADDED]'          => $dateAdded, // 1.9.33
            '[SUBSCRIBER_DATETIME_ADDED]'      => $dateTimeAdded, // 1.9.33
            '[SUBSCRIBER_LAST_OPEN_DATE]'      => $lastOpenDate,
            '[SUBSCRIBER_LAST_CLICK_DATE]'     => $lastClickDate,
            '[SUBSCRIBER_LAST_SEND_DATE]'      => $lastSendDate,
            '[SUBSCRIBER_LAST_OPEN_DATETIME]'  => $lastOpenDatetime,
            '[SUBSCRIBER_LAST_CLICK_DATETIME]' => $lastClickDatetime,
            '[SUBSCRIBER_LAST_SEND_DATETIME]'  => $lastSendDatetime,
        ];

        return (array)hooks()->applyFilters('list_field_get_default_value_tags', $tags);
    }

    /**
     * @param string $value
     * @param ListSubscriber|null $subscriber
     *
     * @return string
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function parseDefaultValueTags(string $value, ?ListSubscriber $subscriber = null): string
    {
        if (empty($value)) {
            return $value;
        }
        $tags = self::getDefaultValueTags($subscriber);

        return (string)str_replace(array_keys($tags), array_values($tags), $value);
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        // make sure we uppercase the tags
        $this->tag = strtoupper((string)$this->tag);
        return parent::beforeValidate();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function beforeSave()
    {
        // make sure the email field is always visible
        if ($this->tag === 'EMAIL') {
            $this->visibility = self::VISIBILITY_VISIBLE;
        }

        return parent::beforeSave();
    }
}
