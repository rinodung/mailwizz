<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTemplateUrlActionListField
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{campaign_template_url_action_list_field}}".
 *
 * The followings are the available columns in table '{{campaign_template_url_action_list_field}}':
 * @property string $url_id
 * @property integer $campaign_id
 * @property integer $template_id
 * @property integer $list_id
 * @property integer $field_id
 * @property string $field_value
 * @property string $url
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property Lists $list
 * @property CampaignTemplate $template
 * @property ListField $field
 */
class CampaignTemplateUrlActionListField extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_template_url_action_list_field}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['field_id, field_value, url', 'required'],
            ['field_id', 'numerical', 'integerOnly'=>true],
            ['field_id', 'exist', 'className' => ListField::class],
            ['field_value', 'length', 'max'=>255],
            ['url', '_validateUrl'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'list'       => [self::BELONGS_TO, Lists::class, 'list_id'],
            'template'   => [self::BELONGS_TO, CampaignTemplate::class, 'template_id'],
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
            'url_id'         => t('campaigns', 'Url'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'template_id'    => t('campaigns', 'Template'),
            'list_id'        => t('campaigns', 'List'),
            'field_id'       => t('campaigns', 'Field'),
            'field_value'    => t('campaigns', 'Field value'),
            'url'            => t('campaigns', 'Url'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'url'         => t('campaigns', 'Trigger the selected action when the subscriber will access this url'),
            'field_id'    => t('campaigns', 'Which field to change when the subscriber opens the campaign.'),
            'field_value' => t('campaigns', 'The value that the custom field should get after the subscriber clicks the link in the campaign'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTemplateUrlActionListField the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTemplateUrlActionListField $model */
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
    public function getParsedFieldValueByListFieldValue(CAttributeCollection $collection): string
    {
        $collection->add('fieldValue', $this->field_value);
        return CampaignHelper::getParsedFieldValueByListFieldValue($collection);
    }

    /**
     * @return void
     *
     * @param string $attribute
     * @param array $params
     */
    public function _validateUrl(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        // if this is a URL tag
        if (preg_match('/^\[([A-Z_]+)_URL\]$/', $this->$attribute, $matches)) {
            return;
        }

        // if this is a regular url
        $validator = new CUrlValidator();
        if ($validator->validateValue($this->$attribute)) {
            return;
        }

        $this->addError($attribute, t('campaigns', 'Please provide a valid url!'));
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->url = StringHelper::normalizeUrl($this->url);
        return parent::beforeSave();
    }
}
