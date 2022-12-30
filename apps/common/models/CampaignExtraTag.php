<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignExtraTag
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.3
 */

/**
 * This is the model class for table "campaign_extra_tag".
 *
 * The followings are the available columns in table 'campaign_extra_tag':
 * @property integer $tag_id
 * @property integer $campaign_id
 * @property string $tag
 * @property string $content
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 */
class CampaignExtraTag extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_extra_tag}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['tag, content', 'required'],
            ['tag', 'length', 'min' => 1, 'max' => 50],
            ['tag', 'match', 'pattern' => '#^(([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}]+)([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9\_]+)?([A-Z\p{Cyrillic}\p{Arabic}\p{Greek}0-9]+)?)$#u'],
            ['content', 'length', 'max' => 65535],
        ];

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
            'tag_id'        => t('campaigns', 'Tag'),
            'campaign_id'   => t('campaigns', 'Campaign'),
            'tag'           => t('campaigns', 'Tag'),
            'content'       => t('campaigns', 'Content'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignExtraTag the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignExtraTag $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getFullTagWithPrefix(): string
    {
        return '[' . self::getTagPrefix() . $this->tag . ']';
    }

    /**
     * @return string
     */
    public static function getTagPrefix(): string
    {
        return (string)app_param('customer.campaigns.extra_tags.prefix', 'CET_');
    }
}
