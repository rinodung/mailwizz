<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignAbtestSubjectToDeliveryLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

/**
 * This is the model class for table "{{campaign_abtest_subject_to_delivery_log}}".
 *
 * The followings are the available columns in table '{{campaign_abtest_subject_to_delivery_log}}':
 * @property integer $subject_id
 * @property integer $log_id
 *
 * The followings are the available model relations:
 * @property CampaignAbtestSubject $subject
 * @property CampaignDeliveryLog $log
 */
class CampaignAbtestSubjectToDeliveryLog extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_abtest_subject_to_delivery_log}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array relational rules.
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'subject'   => [self::BELONGS_TO, 'CampaignAbtestSubject', 'subject_id'],
            'log'       => [self::BELONGS_TO, 'CampaignDeliveryLog', 'log_id'],
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
            'log_id'        => t('campaigns', 'Log'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAbtestSubjectToDeliveryLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
