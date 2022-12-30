<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberOptoutHistory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 */

/**
 * This is the model class for table "list_subscriber_optout_history".
 *
 * The followings are the available columns in table 'list_subscriber_optout_history':
 * @property integer $subscriber_id
 * @property string $optout_ip
 * @property string|CDbExpression $optout_date
 * @property string $optout_user_agent
 * @property string $confirm_ip
 * @property string|CDbExpression $confirm_date
 * @property string $confirm_user_agent
 *
 * The followings are the available model relations:
 * @property ListSubscriber $subscriber
 */
class ListSubscriberOptoutHistory extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber_optout_history}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return CMap::mergeArray([], parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'subscriber' => [self::BELONGS_TO, ListSubscriber::class, 'subscriber_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'subscriber_id'      => t('list_subscribers', 'Subscriber'),
            'optout_ip'          => t('list_subscribers', 'Opt-out ip'),
            'optout_date'        => t('list_subscribers', 'Opt-out date'),
            'optout_user_agent'  => t('list_subscribers', 'Opt-out user agent'),
            'confirm_ip'         => t('list_subscribers', 'Confirm ip'),
            'confirm_date'       => t('list_subscribers', 'Confirm date'),
            'confirm_user_agent' => t('list_subscribers', 'Confirm user agent'),
        ];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberOptoutHistory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberOptoutHistory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getOptoutDate(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDateTime((string)$this->optout_date);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getConfirmDate(): string
    {
        return $this->dateTimeFormatter->formatLocalizedDateTime((string)$this->confirm_date);
    }
}
