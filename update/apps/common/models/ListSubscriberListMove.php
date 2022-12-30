<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberListMove
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.3
 */

/**
 * This is the model class for table "list_subscriber_list_move".
 *
 * The followings are the available columns in table 'list_subscriber_list_move':
 * @property integer $id
 * @property integer $source_subscriber_id
 * @property integer $source_list_id
 * @property integer $destination_subscriber_id
 * @property integer $destination_list_id
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListSubscriber $subscriber
 * @property Lists $sourceList
 * @property ListSubscriber $sourceSubscriber
 * @property Lists $destinationList
 * @property ListSubscriber $destinationSubscriber
 */
class ListSubscriberListMove extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber_list_move}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [
            'sourceSubscriber'      => [self::BELONGS_TO, ListSubscriber::class, 'source_subscriber_id'],
            'sourceList'            => [self::BELONGS_TO, Lists::class, 'source_list_id'],
            'destinationSubscriber' => [self::BELONGS_TO, ListSubscriber::class, 'destination_subscriber_id'],
            'destinationList'       => [self::BELONGS_TO, Lists::class, 'destination_list_id'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberListMove the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberListMove $model */
        $model = parent::model($className);

        return $model;
    }
}
