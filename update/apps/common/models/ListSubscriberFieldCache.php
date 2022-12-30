<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberFieldCache
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

/**
 * This is the model class for table "list_subscriber_field_cache".
 *
 * The followings are the available columns in table 'list_subscriber_field_cache':
 * @property integer $subscriber_id
 * @property mixed $data
 *
 * The followings are the available model relations:
 * @property ListSubscriber $subscriber
 */
class ListSubscriberFieldCache extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber_field_cache}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [];
        return CMap::mergeArray($rules, parent::rules());
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
        $labels = [];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberFieldCache the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberFieldCache $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->data = (string)json_encode($this->data);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        $this->data = (array)json_decode((string)$this->data, true);
        parent::afterSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->data = (array)json_decode((string)$this->data, true);
        parent::afterFind();
    }
}
