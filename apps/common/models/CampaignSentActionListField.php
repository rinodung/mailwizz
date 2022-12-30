<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignSentActionListField
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

/**
 * This is the model class for table "{{campaign_sent_action_list_field}}".
 *
 * The followings are the available columns in table '{{campaign_sent_action_list_field}}':
 * @property string $action_id
 * @property integer $campaign_id
 * @property integer $list_id
 * @property integer $field_id
 * @property string $field_value
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Lists $list
 * @property Campaign $campaign
 * @property ListField $field
 */
class CampaignSentActionListField extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_sent_action_list_field}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['field_id, field_value', 'required'],
            ['field_id', 'numerical', 'integerOnly'=>true],
            ['field_value', 'length', 'max' => 255],
            ['field_id', 'exist', 'className' => ListField::class],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list'       => [self::BELONGS_TO, Lists::class, 'list_id'],
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'field'      => [self::BELONGS_TO, ListField::class, 'field_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'action_id'      => t('campaigns', 'Action'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'list_id'        => t('campaigns', 'List'),
            'field_id'       => t('campaigns', 'Field'),
            'field_value'    => t('campaigns', 'Field value'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'field_id'    => t('campaigns', 'Which field to change when the campaign is sent to the subscriber.'),
            'field_value' => t('campaigns', 'The value that the custom field should get after the campaign is sent to subscriber.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignSentActionListField the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignSentActionListField $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getCustomFieldsAsDropDownOptions(): array
    {
        $this->list_id  = (int)$this->list_id;
        static $options = [];
        if (isset($options[$this->list_id])) {
            return $options[$this->list_id];
        }

        $typeIds   = [];
        $typeNames = ['text', 'date', 'datetime', 'textarea', 'country', 'state', 'dropdown'];
        foreach ($typeNames as $typeName) {
            $type = ListFieldType::model()->findByAttributes(['identifier' => $typeName]);
            if (empty($type)) {
                continue;
            }
            $typeIds[] = (int)$type->type_id;
        }

        if (empty($typeIds)) {
            return $options[$this->list_id] = [];
        }

        $options[$this->list_id] = [];
        $criteria = new CDbCriteria();
        $criteria->select = 'field_id, label';
        $criteria->compare('list_id', $this->list_id);
        $criteria->addInCondition('type_id', $typeIds);
        $criteria->addNotInCondition('tag', ['EMAIL']);
        $criteria->order = 'sort_order ASC';
        $models = ListField::model()->findAll($criteria);
        foreach ($models as $model) {
            $options[$this->list_id][$model->field_id] = $model->label;
        }
        return $options[$this->list_id];
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return string
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function getParsedFieldValueByListFieldValue(CAttributeCollection $collection)
    {
        $collection->add('fieldValue', $this->field_value);
        return CampaignHelper::getParsedFieldValueByListFieldValue($collection);
    }
}
