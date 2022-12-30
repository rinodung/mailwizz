<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignGroupBlockSubscriber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.8.2
 */


/**
 * This is the model class for table "{{campaign_group_block_subscriber}}".
 *
 * The followings are the available columns in table '{{campaign_group_block_subscriber}}':
 * @property integer $group_id
 * @property integer $subscriber_id
 */
class CampaignGroupBlockSubscriber extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_group_block_subscriber}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignGroupBlockSubscriber the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignGroupBlockSubscriber $model */
        $model = parent::model($className);

        return $model;
    }
}
