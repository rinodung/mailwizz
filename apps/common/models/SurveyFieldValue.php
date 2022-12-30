<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldValue
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "{{survey_field_value}}".
 *
 * The followings are the available columns in table '{{survey_field_value}}':
 * @property integer $value_id
 * @property integer $field_id
 * @property integer $responder_id
 * @property string $value
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property SurveyField $field
 * @property SurveyResponder $responder
 */
class SurveyFieldValue extends ActiveRecord
{
    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey_field_value}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['value', 'length', 'max'=>255],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'field'     => [self::BELONGS_TO, SurveyField::class, 'field_id'],
            'responder' => [self::BELONGS_TO, SurveyResponder::class, 'responder_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'value_id'      => t('survey_fields', 'Value'),
            'field_id'      => t('survey_fields', 'Field'),
            'responder_id'  => t('survey_fields', 'Responder'),
            'value'         => t('survey_fields', 'Value'),
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

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'value_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveyFieldValue the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveyFieldValue $model */
        $model = parent::model($className);

        return $model;
    }
}
