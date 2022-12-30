<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackOpenWebhookQueue
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.8
 */

/**
 * This is the model class for table "{{campaign_track_open_webhook_queue}}".
 *
 * The followings are the available columns in table '{{campaign_track_open_webhook_queue}}':
 * @property string $id
 * @property integer $webhook_id
 * @property string $track_open_id
 * @property integer $retry_count
 * @property string $next_retry
 *
 * The followings are the available model relations:
 * @property CampaignTrackOpenWebhook $webhook
 * @property CampaignTrackOpen $trackOpen
 */
class CampaignTrackOpenWebhookQueue extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_track_open_webhook_queue}}';
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
        $relations = [
            'webhook'   => [self::BELONGS_TO, CampaignTrackOpenWebhook::class, 'webhook_id'],
            'trackOpen' => [self::BELONGS_TO, CampaignTrackOpen::class, 'track_open_id'],
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
            'webhook_id'    => t('campaigns', 'Webhook'),
            'track_open_id' => t('campaigns', 'Track open'),
            'retry_count'   => t('campaigns', 'Retry count'),
            'next_retry'    => t('campaigns', 'Next retry'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignTrackOpenWebhookQueue the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignTrackOpenWebhookQueue $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->next_retry) || (int)strtotime((string)$this->next_retry) < time()) {
            $this->next_retry = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }
}
