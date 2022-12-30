<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackUnsubscribe
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

/**
 * This is the model class for table "campaign_track_unsubscribe".
 *
 * The followings are the available columns in table 'campaign_track_unsubscribe':
 * @property string $id
 * @property integer $campaign_id
 * @property integer $subscriber_id
 * @property string $location_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string $reason
 * @property string $note
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 * @property IpLocation $ipLocation
 */
class CampaignTrackUnsubscribe extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_track_unsubscribe}}';
    }

    /**
     * @return array
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
            'id'             => t('campaigns', 'ID'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'subscriber_id'  => t('campaigns', 'Subscriber'),
            'location_id'    => t('campaigns', 'Location'),
            'ip_address'     => t('campaigns', 'Ip address'),
            'user_agent'     => t('campaigns', 'User agent'),
            'reason'		 => t('campaigns', 'Unsubscribe reason'),
            'note'           => t('campaigns', 'Note'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTrackUnsubscribe the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTrackUnsubscribe $model */
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
