<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveySegmentOperator
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "list_segment_operator".
 *
 * The followings are the available columns in table 'list_segment_operator':
 * @property integer $operator_id
 * @property string $name
 * @property string $slug
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property SurveySegmentCondition[] $segmentConditions
 */
class SurveySegmentOperator extends ActiveRecord
{
    /**
     * Operators list
     */
    const IS = 'is';
    const IS_NOT = 'is-not';
    const CONTAINS = 'contains';
    const NOT_CONTAINS = 'not-contains';
    const STARTS_WITH = 'starts';
    const ENDS_WITH = 'ends';
    const GREATER = 'greater';
    const LESS = 'less';
    const NOT_STARTS_WITH = 'not-starts';
    const NOT_ENDS_WITH = 'not-ends';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_segment_operator}}';
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'segmentConditions' => [self::HAS_MANY, SurveySegmentCondition::class, 'operator_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveySegmentOperator the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveySegmentOperator $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        return false;
    }
}
