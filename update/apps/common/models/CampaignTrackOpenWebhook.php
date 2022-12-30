<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackOpenWebhook
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.8
 */

/**
 * This is the model class for table "{{campaign_track_open_webhook}}".
 *
 * The followings are the available columns in table '{{campaign_track_open_webhook}}':
 * @property integer $webhook_id
 * @property integer $campaign_id
 * @property string $webhook_url
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property CampaignTrackOpenWebhookQueue[] $campaignTrackOpenWebhookQueues
 */
class CampaignTrackOpenWebhook extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_track_open_webhook}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['webhook_url', 'required'],
            ['webhook_url', 'length', 'max'=>255],
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
            'campaign'                       => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'campaignTrackOpenWebhookQueues' => [self::HAS_MANY, CampaignTrackOpenWebhookQueue::class, 'webhook_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'webhook_id'  => t('campaigns', 'Webhook'),
            'campaign_id' => t('campaigns', 'Campaign'),
            'webhook_url' => t('campaigns', 'Webhook url'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $labels = [
            'webhook_url' => 'https://www.website.com/process-incoming-data.php',
        ];

        return CMap::mergeArray($labels, parent::attributePlaceholders());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTrackOpenWebhook the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTrackOpenWebhook $model */
        $model = parent::model($className);

        return $model;
    }
}
