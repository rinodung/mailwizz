<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListCustomerNotification
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_customer_notification".
 *
 * The followings are the available columns in table 'list_customer_notification':
 * @property integer $list_id
 * @property string $daily
 * @property string $subscribe
 * @property string $unsubscribe
 * @property string $daily_to
 * @property string $subscribe_to
 * @property string $unsubscribe_to
 *
 * The followings are the available model relations:
 * @property Lists $list
 */
class ListCustomerNotification extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_customer_notification}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['daily, subscribe, unsubscribe', 'required'],
            ['daily, subscribe, unsubscribe', 'in', 'range'=>[self::TEXT_YES, self::TEXT_NO]],
            ['daily_to, subscribe_to, unsubscribe_to', 'length', 'max'=>255],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'list' => [self::BELONGS_TO, Lists::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'           => t('lists', 'List'),
            'daily'             => t('lists', 'Daily'),
            'subscribe'         => t('lists', 'Subscribe'),
            'unsubscribe'       => t('lists', 'Unsubscribe'),
            'daily_to'          => t('lists', 'Daily to'),
            'subscribe_to'      => t('lists', 'Subscribe to'),
            'unsubscribe_to'    => t('lists', 'Unsubscribe to'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListCustomerNotification the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListCustomerNotification $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getYesNoDropdownOptions(): array
    {
        return [
            ''              => t('app', 'Choose'),
            self::TEXT_YES  => t('app', 'Yes'),
            self::TEXT_NO   => t('app', 'No'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'subscribe'         => t('lists', 'Whether to send notifications when a new subscriber will join the list.'),
            'unsubscribe'       => t('lists', 'Whether to send notifications when a new subscriber will leave the list.'),
            'subscribe_to'      => t('lists', 'Where to send the subscribe notifications, separate multiple email addresses by a comma.'),
            'unsubscribe_to'    => t('lists', 'Where to send the unsubscribe notifications, separate multiple email addresses by a comma.'),
        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'subscribe'         => t('lists', ''),
            'unsubscribe'       => t('lists', ''),
            'subscribe_to'      => t('lists', 'me@mydomain.com'),
            'unsubscribe_to'    => t('lists', 'me@mydomain.com'),
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }
}
