<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignAbtestSubjectToTrackOpen
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
 * @property integer $open_id
 *
 * The followings are the available model relations:
 * @property CampaignAbtestSubject $subject
 * @property CampaignTrackOpen $open
 */
class CampaignAbtestSubjectToTrackOpen extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_abtest_subject_to_track_open}}';
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
            'open'      => [self::BELONGS_TO, 'CampaignTrackOpen', 'open_id'],
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
            'open_id'       => t('campaigns', 'Open'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAbtestSubjectToTrackOpen the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}
