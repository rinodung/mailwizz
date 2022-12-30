<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignAbtestSubject
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

/**
 * This is the model class for table "{{campaign_abtest_subject}}".
 *
 * The followings are the available columns in table '{{campaign_abtest_subject}}':
 * @property integer $subject_id
 * @property integer $test_id
 * @property string $subject
 * @property string $is_winner
 * @property integer $opens_count
 * @property integer $usage_count
 * @property string $status
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignAbtest $test
 * @property CampaignDeliveryLog[] $deliveryLogs
 */
class CampaignAbtestSubject extends ActiveRecord
{
    /**
     * Flags
     */
    const STATUS_PENDING_DELETE = 'pending-delete';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_abtest_subject}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['subject', 'required'],
            ['subject', 'filter', 'filter' => 'trim'],
            ['subject', 'length', 'min' => 1, 'max' => 500],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'test'         => [self::BELONGS_TO, 'CampaignAbtest', 'test_id'],
            'deliveryLogs' => [self::MANY_MANY, 'CampaignDeliveryLog', '{{campaign_abtest_subject_to_delivery_log}}(subject_id, log_id)'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'subject_id'    => t('campaigns', 'Subject'),
            'test_id'       => t('campaigns', 'Test'),
            'subject'       => t('campaigns', 'Subject'),
            'is_winner'     => t('campaigns', 'Is winner'),
            'opens_count'   => t('campaigns', 'Opens count'),
            'usage_count'   => t('campaigns', 'Usage count'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAbtestSubject the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return bool
     */
    public function getIsWinner(): bool
    {
        return (string)$this->is_winner === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getUsageCountMutexKey(): string
    {
        return sprintf('%s:%d', __METHOD__, $this->subject_id);
    }

    /**
     * @return string
     */
    public function getOpensCountMutexKey(): string
    {
        return sprintf('%s:%d', __METHOD__, $this->subject_id);
    }

    /**
     * @param int $by
     *
     * @return bool
     */
    public function incrementUsageCount(int $by = 1): bool
    {
        if (!mutex()->acquire($this->getUsageCountMutexKey(), 10)) {
            return false;
        }

        try {
            $updated = $this->updateCounters(['usage_count' => $by], 'subject_id = :sid', [':sid' => (int)$this->subject_id]);
            return !empty($updated);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        } finally {
            mutex()->release($this->getUsageCountMutexKey());
        }

        return false;
    }

    /**
     * @param int $by
     *
     * @return bool
     */
    public function incrementOpensCount(int $by = 1): bool
    {
        if (!mutex()->acquire($this->getOpensCountMutexKey(), 10)) {
            return false;
        }

        try {
            $updated = $this->updateCounters(['opens_count' => $by], 'subject_id = :sid', [':sid' => (int)$this->subject_id]);
            return !empty($updated);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        } finally {
            mutex()->release($this->getOpensCountMutexKey());
        }

        return false;
    }

    /**
     * @param int $count
     *
     * @return bool
     */
    public function saveOpensCount(int $count): bool
    {
        if (!mutex()->acquire($this->getOpensCountMutexKey(), 10)) {
            return false;
        }

        try {
            return $this->saveAttributes(['opens_count' => $count]);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        } finally {
            mutex()->release($this->getOpensCountMutexKey());
        }

        return false;
    }
}
