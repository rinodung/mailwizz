<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Option
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "option".
 *
 * The followings are the available columns in table 'option':
 * @property string $category
 * @property string $key
 * @property string $value
 * @property integer $is_serialized
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 */
class Option extends ActiveRecord
{
    /**
     * @var int
     */
    private static $_index = 0;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{option}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['category, key, value', 'required'],
            ['is_serialized', 'numerical', 'integerOnly'=>true],
            ['category, key', 'length', 'max'=>100],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        $relations = [];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'category'      => t('options', 'Category'),
            'key'           => t('options', 'Key'),
            'value'         => t('options', 'Value'),
            'is_serialized' => t('options', 'Is serialized'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Option the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Option $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return self::$_index++;
    }
}
