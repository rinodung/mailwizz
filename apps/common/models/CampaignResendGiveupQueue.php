<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignResendGiveupQueue
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 */

/**
 * This is the model class for table "{{campaign_resend_giveup_queue}}".
 *
 * The followings are the available columns in table '{{campaign_resend_giveup_queue}}':
 * @property integer $campaign_id
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 */
class CampaignResendGiveupQueue extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_resend_giveup_queue}}';
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
            'campaign' => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'campaign_id' => t('campaigns', 'Campaign'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignResendGiveupQueue the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignResendGiveupQueue $model */
        $model = parent::model($className);

        return $model;
    }
}
