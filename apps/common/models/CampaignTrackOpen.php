<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackOpen
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign_track_open".
 *
 * The followings are the available columns in table 'campaign_track_open':
 * @property string $id
 * @property integer $campaign_id
 * @property integer $subscriber_id
 * @property integer $location_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 * @property IpLocation $ipLocation
 */
class CampaignTrackOpen extends ActiveRecord
{
    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [];

        if (app_param('send.campaigns.command.useTempQueueTables', false)) {
            $behaviors['toQueueTable'] = [
                'class' => 'common.components.db.behaviors.CampaignOpenToCampaignQueueTableBehavior',
            ];
        }

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_track_open}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['date_added', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'subscriber' => [self::BELONGS_TO, ListSubscriber::class, 'subscriber_id'],
            'ipLocation' => [self::BELONGS_TO, IpLocation::class, 'location_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'id'            => t('campaigns', 'ID'),
            'campaign_id'   => t('campaigns', 'Campaign'),
            'subscriber_id' => t('campaigns', 'Subscriber'),
            'location_id'   => t('campaigns', 'Location'),
            'ip_address'    => t('campaigns', 'Ip address'),
            'user_agent'    => t('campaigns', 'User agent'),
            'open_times'    => t('campaigns', 'Open times'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTrackOpen the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTrackOpen $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getIpWithLocationForGrid(): string
    {
        if (empty($this->ipLocation)) {
            return (string)$this->ip_address;
        }

        return $this->ip_address . ' <br />(' . $this->ipLocation->getLocation() . ')';
    }
}
