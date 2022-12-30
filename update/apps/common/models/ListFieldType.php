<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldType
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_field_type".
 *
 * The followings are the available columns in table 'list_field_type':
 * @property integer $type_id
 * @property string $name
 * @property string $identifier
 * @property string $class_alias
 * @property string $description
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListField[] $fields
 */
class ListFieldType extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_field_type}}';
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'fields' => [self::HAS_MANY, ListField::class, 'type_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'type_id'       => t('list_fields', 'Type'),
            'name'          => t('list_fields', 'Name'),
            'identifier'    => t('list_fields', 'Identifier'),
            'class_alias'   => t('list_fields', 'Class alias'),
            'description'   => t('list_fields', 'Description'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListFieldType the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListFieldType $model */
        $model = parent::model($className);

        return $model;
    }
}
