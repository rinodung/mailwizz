<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TransactionalEmailLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{transactional_email_log}}".
 *
 * The followings are the available columns in table '{{transactional_email_log}}':
 * @property integer $log_id
 * @property integer $email_id
 * @property string $message
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property TransactionalEmail $email
 */
class TransactionalEmailLog extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{transactional_email_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            // The following rule is used by search().
            ['email_id, message', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'email' => [self::BELONGS_TO, TransactionalEmail::class, 'email_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'log_id'     => t('transactional_emails', 'Log'),
            'email_id'   => t('transactional_emails', 'Email'),
            'message'    => t('transactional_emails', 'Message'),
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

        $criteria->compare('t.email_id', $this->email_id);
        $criteria->compare('t.message', $this->message, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.log_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TransactionalEmailLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var TransactionalEmailLog $model */
        $model = parent::model($className);

        return $model;
    }
}
