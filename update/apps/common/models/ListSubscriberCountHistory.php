<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriberCountHistory
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.34
 */

/**
 * This is the model class for table "list_subscriber_count_history".
 *
 * The followings are the available columns in table 'list_subscriber_count_history':
 * @property integer|null $id
 * @property integer $list_id
 * @property integer $total
 * @property integer $confirmed_total
 * @property integer $unconfirmed_total
 * @property integer $blacklisted_total
 * @property integer $unsubscribed_total
 * @property integer $moved_total
 * @property integer $disabled_total
 * @property integer $unapproved_total
 *
 * @property integer $confirmed_hourly
 * @property integer $unconfirmed_hourly
 * @property integer $blacklisted_hourly
 * @property integer $unsubscribed_hourly
 * @property integer $moved_hourly
 * @property integer $disabled_hourly
 * @property integer $unapproved_hourly
 *
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Lists $list
 */
class ListSubscriberCountHistory extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber_count_history}}';
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
            'list_id'             => t('lists', 'List'),

            'total'               => t('lists', 'Total'),
            'confirmed_total'     => t('lists', 'Confirmed total'),
            'unconfirmed_total'   => t('lists', 'Unconfirmed total'),
            'blacklisted_total'   => t('lists', 'Blacklisted total'),
            'unsubscribed_total'  => t('lists', 'Unsubscribed total'),
            'moved_total'         => t('lists', 'Moved total'),
            'disabled_total'      => t('lists', 'Disabled total'),
            'unapproved_total'    => t('lists', 'Unapproved total'),

            'confirmed_hourly'    => t('lists', 'Confirmed hourly'),
            'unconfirmed_hourly'  => t('lists', 'Unconfirmed hourly'),
            'blacklisted_hourly'  => t('lists', 'Blacklisted hourly'),
            'unsubscribed_hourly' => t('lists', 'Unsubscribed hourly'),
            'moved_hourly'        => t('lists', 'Moved hourly'),
            'disabled_hourly'     => t('lists', 'Disabled hourly'),
            'unapproved_hourly'   => t('lists', 'Unapproved hourly'),
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
        $criteria->compare('t.list_id', (int)$this->list_id);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'id'    => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriberCountHistory the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriberCountHistory $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param \Carbon\Carbon $dateStart
     * @param \Carbon\Carbon $dateEnd
     * @return bool
     */
    public function calculate(Carbon\Carbon $dateStart, Carbon\Carbon $dateEnd): bool
    {
        if (empty($this->list_id)) {
            return false;
        }

        $this->total              = $this->countByStatusAndDateTime();
        $this->confirmed_total    = $this->countByStatusAndDateTime(ListSubscriber::STATUS_CONFIRMED);
        $this->unconfirmed_total  = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNCONFIRMED);
        $this->blacklisted_total  = $this->countByStatusAndDateTime(ListSubscriber::STATUS_BLACKLISTED);
        $this->unsubscribed_total = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNSUBSCRIBED);
        $this->moved_total        = $this->countByStatusAndDateTime(ListSubscriber::STATUS_MOVED);
        $this->disabled_total     = $this->countByStatusAndDateTime(ListSubscriber::STATUS_DISABLED);
        $this->unapproved_total   = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNAPPROVED);

        $this->confirmed_hourly    = $this->countByStatusAndDateTime(ListSubscriber::STATUS_CONFIRMED, $dateStart, $dateEnd);
        $this->unconfirmed_hourly  = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNCONFIRMED, $dateStart, $dateEnd);
        $this->blacklisted_hourly  = $this->countByStatusAndDateTime(ListSubscriber::STATUS_BLACKLISTED, $dateStart, $dateEnd);
        $this->unsubscribed_hourly = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNSUBSCRIBED, $dateStart, $dateEnd);
        $this->moved_hourly        = $this->countByStatusAndDateTime(ListSubscriber::STATUS_MOVED, $dateStart, $dateEnd);
        $this->disabled_hourly     = $this->countByStatusAndDateTime(ListSubscriber::STATUS_DISABLED, $dateStart, $dateEnd);
        $this->unapproved_hourly   = $this->countByStatusAndDateTime(ListSubscriber::STATUS_UNAPPROVED, $dateStart, $dateEnd);

        return true;
    }

    /**
     * @param string $status
     * @param \Carbon\Carbon|null $dateStart
     * @param \Carbon\Carbon|null $dateEnd
     * @return int
     */
    public function countByStatusAndDateTime(string $status = '', ?Carbon\Carbon $dateStart = null, ?Carbon\Carbon $dateEnd = null): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);
        if ($status) {
            $criteria->compare('t.status', $status);
        }

        if ($dateStart && $dateEnd) {
            $criteria->addCondition('t.last_updated >= :dateStart AND t.last_updated < :dateEnd');
            $criteria->params[':dateStart'] = $dateStart->format('Y-m-d H:i:s');
            $criteria->params[':dateEnd']   = $dateEnd->format('Y-m-d H:i:s');
        }
        return (int)ListSubscriber::model()->count($criteria);
    }
}
