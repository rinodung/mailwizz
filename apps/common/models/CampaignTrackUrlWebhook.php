<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackUrlWebhook
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.8
 */

/**
 * This is the model class for table "{{campaign_track_url_webhook}}".
 *
 * The followings are the available columns in table '{{campaign_track_url_webhook}}':
 * @property integer $webhook_id
 * @property integer $campaign_id
 * @property string $webhook_url
 * @property string $track_url
 * @property string $track_url_hash
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property CampaignTrackUrlWebhookQueue[] $campaignTrackUrlWebhookQueues
 */
class CampaignTrackUrlWebhook extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_track_url_webhook}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['webhook_url, track_url', 'required'],
            ['webhook_url, track_url', 'length', 'max' => 255],
            ['webhook_url', 'url'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'                      => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'campaignTrackUrlWebhookQueues' => [self::HAS_MANY, CampaignTrackUrlWebhookQueue::class, 'webhook_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'webhook_id'    => t('campaigns', 'Webhook'),
            'campaign_id'   => t('campaigns', 'Campaign'),
            'webhook_url'   => t('campaigns', 'Webhook url'),
            'track_url'     => t('campaigns', 'Url'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTrackUrlWebhook the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTrackUrlWebhook $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->track_url_hash = sha1($this->campaign->campaign_uid . $this->track_url);
        return parent::beforeSave();
    }
}
