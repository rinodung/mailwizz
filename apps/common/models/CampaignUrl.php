<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignUrl
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign_url".
 *
 * The followings are the available columns in table 'campaign_url':
 * @property string $url_id
 * @property integer $campaign_id
 * @property string $hash
 * @property string $destination
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property CampaignTrackUrl[] $trackUrls
 * @property Campaign $campaign
 */
class CampaignUrl extends ActiveRecord
{
    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_url}}';
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
            'trackUrls'         => [self::HAS_MANY, CampaignTrackUrl::class, 'url_id'],
            'trackUrlsCount'    => [self::STAT, CampaignTrackUrl::class, 'url_id'],
            'campaign'          => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'url_id'        => t('campaigns', 'Url'),
            'campaign_id'   => t('campaigns', 'Campaign'),
            'hash'          => t('campaigns', 'Hash'),
            'destination'   => t('campaigns', 'Destination'),
            'clicked_times' => t('campaigns', 'Clicked times'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignUrl the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignUrl $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param int $textLength
     *
     * @return string
     */
    public function getDisplayGridDestination(int $textLength = 0): string
    {
        $destination = (string)str_replace('&amp;', '&', (string)$this->destination);
        $text = $destination;
        if ($textLength > 0) {
            $text = StringHelper::truncateLength($text, $textLength);
        }
        if (FilterVarHelper::url($destination)) {
            return CHtml::link($text, $destination, ['target' => '_blank', 'title' => $destination]);
        }
        return $text;
    }
}
