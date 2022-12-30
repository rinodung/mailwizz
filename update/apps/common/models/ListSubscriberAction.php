<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberAction
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * This is the model class for table "{{list_subscriber_action}}".
 *
 * The followings are the available columns in table '{{list_subscriber_action}}':
 * @property integer $action_id
 * @property integer $source_list_id
 * @property string $source_action
 * @property integer $target_list_id
 * @property string $target_action
 *
 * The followings are the available model relations:
 * @property Lists $sourceList
 * @property Lists $targetList
 */
class ListSubscriberAction extends ActiveRecord
{
    /**
     * Actions list
     */
    const ACTION_SUBSCRIBE = 'subscribe';
    const ACTION_UNSUBSCRIBE = 'unsubscribe';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber_action}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['target_list_id', 'required'],
            ['target_list_id', 'numerical', 'integerOnly' => true],
            ['target_list_id', 'exist', 'className' => Lists::class, 'attributeName' => 'list_id'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'sourceList' => [self::BELONGS_TO, Lists::class, 'source_list_id'],
            'targetList' => [self::BELONGS_TO, Lists::class, 'target_list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'action_id'      => t('lists', 'Action'),
            'source_list_id' => t('lists', 'Source list'),
            'source_action'  => t('lists', 'Source action'),
            'target_list_id' => t('lists', 'Target list'),
            'target_action'  => t('lists', 'Target action'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberAction the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberAction $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return [
            self::ACTION_SUBSCRIBE   => t('lists', ucfirst(self::ACTION_SUBSCRIBE)),
            self::ACTION_UNSUBSCRIBE => t('lists', ucfirst(self::ACTION_UNSUBSCRIBE)),
        ];
    }
}
