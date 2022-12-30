<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GuestFailAttempt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * This is the model class for table "{{guest_fail_attempt}}".
 *
 * The followings are the available columns in table '{{guest_fail_attempt}}':
 * @property string $attempt_id
 * @property string $ip_address
 * @property string $ip_address_hash
 * @property string $user_agent
 * @property string $place
 * @property string|CDbExpression $date_added
 */
class GuestFailAttempt extends ActiveRecord
{
    /**
     * Flag for checks interval
     */
    const CHECKS_INTERVAL = 10;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{guest_fail_attempt}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['ip_address, place', 'safe', 'on' => 'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'attempt_id'      => t('guest_fail_attempt', 'Attempt'),
            'ip_address'      => t('guest_fail_attempt', 'Ip address'),
            'ip_address_hash' => t('guest_fail_attempt', 'Ip address hash'),
            'user_agent'      => t('guest_fail_attempt', 'User agent'),
            'place'           => t('guest_fail_attempt', 'Place'),
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

        $criteria->compare('ip_address', $this->ip_address, true);
        $criteria->compare('place', $this->place, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder'     => [
                    'attempt_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return GuestFailAttempt the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var GuestFailAttempt $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return GuestFailAttempt
     */
    public function setBaseInfo(): self
    {
        if (empty($this->ip_address) && !is_cli()) {
            $this->ip_address = (string)request()->getUserHostAddress();
        }
        if (empty($this->ip_address_hash)) {
            $this->ip_address_hash = md5($this->ip_address);
        }
        if (empty($this->user_agent) && !is_cli()) {
            $this->user_agent = (string)request()->getUserAgent();
        }
        return $this;
    }

    /**
     * @param string $place
     *
     * @return GuestFailAttempt
     */
    public function setPlace(string $place): self
    {
        $this->place = $place;
        return $this;
    }

    /**
     * @return int
     */
    public function getFailuresCount(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.ip_address_hash', $this->ip_address_hash);
        $criteria->addCondition(sprintf('t.date_added >= DATE_SUB(NOW(), INTERVAL %d MINUTE)', self::CHECKS_INTERVAL));

        return (int)self::model()->count($criteria);
    }

    /**
     * @return void
     */
    public function deleteFailures(): void
    {
        $criteria = new CDbCriteria();
        $criteria->compare('ip_address_hash', $this->ip_address_hash);
        $criteria->addCondition(sprintf('date_added <= DATE_SUB(NOW(), INTERVAL %d MINUTE)', self::CHECKS_INTERVAL * 2));

        $this->deleteAll($criteria);
    }

    /**
     * @return bool
     */
    public function getHasTooManyFailures(): bool
    {
        $count = $this->getFailuresCount();

        if ($count >= self::CHECKS_INTERVAL) {
            return true;
        }

        $this->deleteFailures();

        return false;
    }

    /**
     * @return bool
     */
    public function getHasTooManyFailuresWithThrottle(): bool
    {
        $count = $this->getFailuresCount();

        // sleep incrementally up to self::CHECKS_INTERVAL
        sleep((int)min($count, self::CHECKS_INTERVAL));

        if ($count >= self::CHECKS_INTERVAL) {
            return true;
        }

        $this->deleteFailures();

        return false;
    }

    /**
     * @param string $place
     *
     * @return bool
     */
    public static function registerByPlace(string $place): bool
    {
        $model = new self();
        $model->setBaseInfo();
        $model->setPlace($place);
        return (bool)$model->save();
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->setBaseInfo();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->setBaseInfo();
        return parent::beforeSave();
    }
}
