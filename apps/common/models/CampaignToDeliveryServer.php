<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignToDeliveryServer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.2
 */

/**
 * This is the model class for table "campaign_to_delivery_server".
 *
 * The followings are the available columns in table 'campaign_to_delivery_server':
 * @property integer|null $campaign_id
 * @property integer|null $server_id
 */
class CampaignToDeliveryServer extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_to_delivery_server}}';
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'campaign_id'    => t('campaigns', 'Campaign'),
            'server_id'      => t('servers', 'Server'),
        ];
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'          => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'deliveryServer'    => [self::BELONGS_TO, DeliveryServer::class, 'server_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignToDeliveryServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignToDeliveryServer $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->campaign_id) || empty($this->server_id)) {
            return false;
        }

        $campaign = Campaign::model()->findByPk((int)$this->campaign_id);
        if (empty($campaign)) {
            return false;
        }

        $server = DeliveryServer::model()->findByPk((int)$this->server_id);
        if (empty($server)) {
            return false;
        }

        return parent::beforeSave();
    }
}
